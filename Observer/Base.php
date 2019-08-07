<?php
namespace RedChamps\IpSecurity\Observer;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use RedChamps\IpSecurity\Model\ConfgManager;
use RedChamps\IpSecurity\Model\EmailSender;
use RedChamps\IpSecurity\Model\IpsecuritylogFactory;
use RedChamps\IpSecurity\Model\IptokenlogFactory;
use RedChamps\IpSecurity\Model\ResourceModel\Ipsecuritylog\CollectionFactory;

abstract class Base implements ObserverInterface
{
    /** IP version 4 */
    const IPV4 = 'IPv4';
    /** IP version 6 */
    const IPV6 = 'IPv6';

    const TOKEN_COOKIE_NAME = 'ip_security_token';

    protected static $_flagCheckToken = 0;

    protected $_redirectPage = null;
    protected $_redirectBlank = null;
    protected $_rawAllowIpData = null;
    protected $_rawBlockIpData = null;
    protected $_rawExceptIpData = null;
    protected $_eventEmail = "";
    protected $_emailTemplate = 0;
    protected $_emailIdentity = null;
    protected $_storeType = null;
    protected $_lastFoundIp = null;
    protected $_isFrontend = false;
    protected $_alwaysNotify = false;
    protected $_eventEmailToken = "";
    protected $_alwaysNotifyToken = false;
    protected $_emailTemplateToken = 0;
    protected $_emailTemplateTokenFail;
    protected $_emailIdentityToken = null;
    protected $_currentIpVersion = null;

    /** @var  ConfgManager */
    protected $_helper;

    /**
     * IP которые нужно игнорировать
     *
     * @var array
     */
    protected $_ignoredIpAddresses = [
        '::', // псевдоним 0000:0000 ... Означает ошибку
        '::ffff' // адресс для совмешения с IPv4
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ConfgManager
     */
    protected $configManager;

    /**
     * @var IptokenlogFactory
     */
    protected $ipSecurityTokenLogFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $ipSecurityLogCollectionFactory;

    /**
     * @var IpsecuritylogFactory
     */
    protected $ipSecurityLogFactory;

    /**
     * @var Http
     */
    protected $request;

    protected $url;

    protected $cmsPageFactory;

    protected $timeZone;

    protected $emailSender;

    protected $phpRequest;

    protected $serializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfgManager $configManager,
        IptokenlogFactory $ipSecurityTokenLogFactory,
        StoreManagerInterface $storeManager,
        CollectionFactory $ipSecurityLogCollectionFactory,
        IpsecuritylogFactory $ipSecurityLogFactory,
        Http $request,
        UrlInterface $url,
        PageFactory $cmsPageFactory,
        TimezoneInterface $timeZone,
        EmailSender $emailSender,
        Request $phpRequest,
        SerializerInterface $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configManager = $configManager;
        $this->ipSecurityTokenLogFactory = $ipSecurityTokenLogFactory;
        $this->storeManager = $storeManager;
        $this->ipSecurityLogCollectionFactory = $ipSecurityLogCollectionFactory;
        $this->ipSecurityLogFactory = $ipSecurityLogFactory;
        $this->request = $request;
        $this->url = $url;
        $this->cmsPageFactory = $cmsPageFactory;
        $this->timeZone = $timeZone;
        $this->emailSender = $emailSender;
        $this->phpRequest = $phpRequest;
        $this->serializer = $serializer;
    }

    /**
     * Trim trailing slashes, except single "/"
     *
     * @param $str string
     * @return string
     */
    protected function trimTrailingSlashes($str)
    {
        $str = trim($str);
        return $str == '/' ? $str : rtrim($str, '/');
    }

    /**
     * load Token config
     */
    protected function _readTokenConfig()
    {
        $this->_eventEmailToken = $this->configManager->readConfig('token/email_event');
        $this->_alwaysNotifyToken = $this->configManager->readConfig('token/email_always');
        $this->_emailTemplateToken = $this->configManager->readConfig('token/email_template');
        $this->_emailTemplateTokenFail = $this->configManager->readConfig('token/fail_email_template');
        $this->_emailIdentityToken = $this->configManager->readConfig('token/email_identity');
    }

    /**
     * Reading configuration for Admin
     */
    protected function _readAdminConfig()
    {
        $this->_redirectPage = $this->trimTrailingSlashes(
            $this->configManager->readConfig('admin/redirect_page')
        );
        $this->_redirectBlank = $this->configManager->readConfig('admin/redirect_blank');
        $this->_rawAllowIpData = $this->configManager->readConfig('admin/allow');
        $this->_rawBlockIpData = $this->configManager->readConfig('admin/block');
        $this->_eventEmail = $this->configManager->readConfig('admin/email_event');
        $this->_emailTemplate = $this->configManager->readConfig('admin/email_template');
        $this->_emailIdentity = $this->configManager->readConfig('admin/email_identity');
        $this->_alwaysNotify = $this->configManager->readConfig('admin/email_always');

        $this->_storeType = __("Admin");
        $this->_isFrontend = false;
    }

    /**
     * Checking current ip for rules
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return Base
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _processIpCheck($observer)
    {
        $currentIps = $this->getCurrentIp();
        foreach ($currentIps as $ipVersion => $currentIp) {
            $this->setCurrentIpVersion($ipVersion);

            $allowIps = $this->_ipTextToArray($this->_rawAllowIpData);
            $blockIps = $this->_ipTextToArray($this->_rawBlockIpData);

            $allow = $this->isIpAllowed($currentIp, $allowIps, $blockIps);

            if (!$allow) {
                $allow = $this->_checkSecurityTokenAccess();
            }

            $this->_processAllowDeny($allow, $currentIp, $observer);
        }

        return $this;
    }

    /**
     * Get IP of current client
     *
     * @return array|String
     */
    public function getCurrentIp()
    {
        $selectedIpVariable = $this->getHelper()->getIpVariable();

        if (!empty($this->phpRequest->getServer($selectedIpVariable))) {
            $currentIp = $this->phpRequest->getServer($selectedIpVariable);
        } elseif (!empty($this->phpRequest->getServer("REMOTE_ADDR"))) { //
            //no default IP variable
            $currentIp = $this->phpRequest->getServer("REMOTE_ADDR");
        } else {
            //unknown IP
            $currentIp = "0.0.0.0";
        }

        /**
         * $currentIp = '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d:217.199.123.24';
         * $currentIp = '::ffff:217.199.123.24';
         * $currentIp = '2001:0db8::1f34:8a2e:07a0:765d';
         * $currentIp = '::07a0:765d';
         * $currentIp = '::07a0:765d';
         * $currentIp = '::ffff:217.199.123.24';
         * $currentIp = '2001:0db8:11a3:09d7:1f34:8a2e:07a0:765a';
         * $currentIp = '217.199.123.24';
         * $currentIp = '';
         * $currentIp = '::10:ff12';
         */
        return $this->_getCurrentIp($currentIp, $selectedIpVariable);
    }

    /**
     * get Helper
     *
     * @return ConfgManager
     */
    public function getHelper()
    {
        if (!$this->_helper) {
            $this->_helper = $this->configManager;
        }
        return $this->_helper;
    }

    /**
     * HTTP_X_FORWARDED_FOR can return comma delimetered list of IP addresses.
     * We need only one IP address to check
     *
     * @param $currentIp
     * @param $selectedIpVariable
     * @return string|array
     */
    protected function _getCurrentIp($currentIp, $selectedIpVariable)
    {
        switch ($selectedIpVariable) {
            case 'HTTP_X_FORWARDED_FOR':
                $resultArray = explode(',', $currentIp);
                $ip = trim($resultArray[0]);
                break;
            default:
                $ip = $currentIp;
        }

        $ipVFour = $this->getIpVFour($ip);
        $ipVSix = $this->getIpVSix($ip);

        switch ($ip) {
            case '::1':
                return [self::IPV4 => '127.0.0.1'];
            case $ipVFour:
                return [self::IPV4 => $ipVFour];
            case $ipVSix:
                return [self::IPV6 => $ipVSix];
            case $ipVSix . ':' . $ipVFour:
                if (in_array($ipVSix, $this->_ignoredIpAddresses)) {
                    $ips[self::IPV4] = $ipVFour;
                } else {
                    $ips[self::IPV6] = $ipVSix;
                }
                return $ips;
            default:
                return [];
        }
    }

    /**
     * Проверяет наличие IPv4
     *
     * @param string $ip
     * @return string
     */
    protected function getIpVFour($ip)
    {
        return $this->getIp($ip, true);
    }

    /**
     * Determines the received IP's version and returns the address in the required version
     * Possible that the server will return two IPs simultaneously IPv6:IPv4
     * IP address conversion to binary for subsequent comparison
     *
     * @param $ip
     * @param bool|true $returnIPVFour
     * @return bool|mixed|string
     */
    protected function getIp($ip, $returnIPVFour = true)
    {
        $ipVFour = null;
        $ipVSix = null;

        //IPv6 IPv4-compatible
        if (substr_count($ip, '.') > 0 && substr_count($ip, ':') > 0) {
            $unlinked = explode(":", $ip);
            $ipVFour = array_pop($unlinked);
            $ipVSix = implode(":", $unlinked);
        } elseif ($this->getIpVersionBy($ip) == self::IPV4) {
            $ipVFour = $ip;
        } elseif ($this->getIpVersionBy($ip) == self::IPV6) {
            $ipVSix = $ip;
        }

        if ($returnIPVFour) {
            return $ipVFour;
        }

        return $ipVSix;
    }

    /**
     * узнаём какой версии IP адресс
     *
     * 1. IPv4
     * 2. IPv6
     * 3. null
     *
     * @param string $ip
     * @param $needle bool|string $needle
     * @return string|null
     */
    protected function getIpVersionBy($ip)
    {
        $version = null;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) > 0) {
            $version = self::IPV4;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $version = self::IPV6;
        } elseif (substr_count($ip, '.') > 0) {
            $version = self::IPV4;
        } elseif (substr_count($ip, ':') > 0) {
            $version = self::IPV6;
        }
        return $version;
    }

    /**
     * Проверяет наличие IPv6
     *
     * @param string $ip
     * @return string
     */
    protected function getIpVSix($ip)
    {
        return $this->getIp($ip, false);
    }

    /**
     * Convert string with IP to IP array
     *
     * @param $text string
     * @return array
     */
    protected function _ipTextToArray($text)
    {
        $ips = preg_split("/[\n\r]+/", $text);
        foreach ($ips as $ipsk => $ipsv) {
            if (trim($ipsv) == "") {
                unset($ips[$ipsk]);
            }
        }
        return $ips;
    }

    /**
     * Check IP for allow/deny rules
     *
     * @param $currentIp string
     * @param $allowIps array
     * @param $blockIps array
     * @return bool
     */
    public function isIpAllowed($currentIp, $allowIps, $blockIps)
    {
        $allow = true;

        # look for allowed
        if ($allowIps) {
            # block all except allowed
            $allow = false;

            # are there any allowed ips
            if ($this->isIpInList($currentIp, $allowIps)) {
                $allow = true;
            }
        }

        # look for blocked
        if ($blockIps) {
            # are there any blocked ips
            if ($this->isIpInList($currentIp, $blockIps)) {
                $allow = false;
            }
        }
        return $allow;
    }

    /**
     * Is ip in list of IP rules
     *
     * @param $searchIp string
     * @param $ipRulesList array
     * @return bool
     */
    public function isIpInList($searchIp, $ipRulesList)
    {
        $searchIpComparable = $this->_convertIpToComparableString($searchIp);
        if (count($ipRulesList) > 0) {
            foreach ($ipRulesList as $ipRule) {
                //ignore comments
                if (strpos($ipRule, "#") === 0) {
                    continue;
                }
                $ip = explode("|", $ipRule);
                $ip = trim($ip[0]);
                try {
                    $ipRange = $this->_convertIpStringToIpRange($ip);

                    if (count($ipRange) == 2) {
                        $ipFrom = $ipRange["first"];
                        $ipTo = $ipRange["last"];
                        //ip versions must be same, length of IPv6 and IPv4 different
                        if (strlen($searchIpComparable) == strlen($ipTo)) {
                            if ((strcmp($ipFrom, $searchIpComparable) <= 0)
                                && (strcmp($searchIpComparable, $ipTo) <= 0)

                            ) {
                                $this->_lastFoundIp = $ipRule;
                                return true;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->getHelper()->log($e->getMessage());
                }
            }
        }
        return false;
    }

    /**
     * Convert IP address (x.xx.xxx.xx) to easy comparable string (xxx.xxx.xxx.xxx)
     *
     * @param string $ip
     * @return string
     * @throws \Exception
     */
    protected function _convertIpToComparableString($ip)
    {
        $ipVersion = $this->getIpVersionBy($ip);
        switch ($ipVersion) {
            case self::IPV4:
                return $this->_convertIpVFourToComparableString($ip);
            case self::IPV6:
                return $this->_convertIpVSixToComparableString($ip);
            default:
                throw new \Exception(
                    sprintf('Unknown ip version %s', $ipVersion)
                );
        }
    }

    /**
     * Преобразование IP версии 4 в строку для проверки
     *
     * @param $ip
     * @return string
     * @throws \Exception
     */
    protected function _convertIpVFourToComparableString($ip)
    {
        $partsOfIp = explode(".", trim($ip));

        if (count($partsOfIp) != 4) {
            throw new \Exception("Incorrect IPv4 format: " . $ip);
        }

        $comparableIpString = sprintf(
            "%03d%03d%03d%03d",
            $partsOfIp[0],
            $partsOfIp[1],
            $partsOfIp[2],
            $partsOfIp[3]
        );

        return $comparableIpString;
    }

    /**
     * Преобразование IP версии 6 в строку для проверки
     *
     * @param $ip
     * @return string
     */
    protected function _convertIpVSixToComparableString($ip)
    {
        $ip = $this->addLostParts($ip);
        $partsOfIp = explode(':', $ip);

        $comparableIpString = strtolower(sprintf(
            "%04s%04s%04s%04s%04s%04s%04s%04s",
            $partsOfIp[0],
            $partsOfIp[1],
            $partsOfIp[2],
            $partsOfIp[3],
            $partsOfIp[4],
            $partsOfIp[5],
            $partsOfIp[6],
            $partsOfIp[7]
        ));

        return $comparableIpString;
    }

    /**
     * IPv6 может содержать не больный адрес
     * Эта функция заполняет отсудствующие элементы
     *
     * @param $ip
     * @return string
     */
    protected function addLostParts($ip)
    {
        $hex = unpack("H*hex", inet_pton($ip));
        $ip = substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);

        return $ip;
    }

    /**
     * Convert IP range as string to array with first and last IP of range
     *
     * @param string $ipRange
     * @return array[first,last]
     * @throws \Exception
     */
    protected function _convertIpStringToIpRange($ipRange)
    {
        $ipVersion = $this->getCurrentIpVersion();
        switch ($ipVersion) {
            case self::IPV4:
                return $this->_convertIpVFourStringToIpRange($ipRange);
            case self::IPV6:
                return $this->_convertIpVSixStringToIpRange($ipRange);
            default:
                throw new \Exception(
                    sprintf("Can not get range. Reason: Unknown ip version %s", $ipVersion)
                );
        }
    }

    /**
     * Получение текущей версии ip клиента
     *
     * @return null
     */
    protected function getCurrentIpVersion()
    {
        return $this->_currentIpVersion;
    }

    /**
     * Установка текущей версии ip клиента
     *
     * @param string $ipVersion
     */
    protected function setCurrentIpVersion($ipVersion)
    {
        $this->_currentIpVersion = $ipVersion;
    }

    /**
     * Преобразовать IP адрес из строки в область для IP версии 4
     *
     * @param string $ipRange
     * @return array
     * @throws \Exception
     */
    protected function _convertIpVFourStringToIpRange($ipRange)
    {
        $ip = explode("|", $ipRange);
        $ip = trim($ip[0]);

        $simpleRange = explode("-", $ip);
        //for xx.xx.xx.xx-yy.yy.yy.yy
        if (count($simpleRange) == 2) {
            $comparableIpRange = $this->getIpRangeBy($simpleRange);
            return $comparableIpRange;
        }
        //for xx.xx.xx.*
        return $this->_processIpPattern($ip);
    }

    /**
     * Диапазон с и до
     *
     * Формат настройки:
     * IPv4-IPv4 или IPv6-IPv6
     *
     * Если будет указан IPv4-IPv6 или IPv6-IPv4,
     * проверка не отработает и в лог запишится запись об ошибке
     *
     * @param array $simpleRange
     * @return array
     * @throws \Exception
     */
    protected function getIpRangeBy($simpleRange)
    {
        $firstIpVersion = $this->getIpVersionBy($simpleRange[0]);
        $lastIpVersion = $this->getIpVersionBy($simpleRange[1]);

        if ($firstIpVersion != $lastIpVersion) {
            throw new \Exception(sprintf(
                'Invalid range ip versions - can not be %s and %s at the same time',
                $firstIpVersion,
                $lastIpVersion
            ));
        }

        $comparableIpRange = [
            'first' => $this->_convertIpToComparableString($simpleRange[0]),
            'last' => $this->_convertIpToComparableString($simpleRange[1])
        ];

        return $comparableIpRange;
    }

    /**
     * Диапазон внутренней компании
     *
     * @param string $ip
     * @return array
     * @throws \Exception
     */
    protected function getIpRangeByMask($ip)
    {
        $fromIp = str_replace('*', '0', $ip);

        $ipVersion = $this->getIpVersionBy($fromIp);

        if ($ipVersion == self::IPV4) {
            $toIp = str_replace('*', '255', $ip);
        } elseif ($ipVersion == self::IPV6) {
            $toIp = str_replace('*', 'ffff', $ip);
        } else {
            throw new \Exception(
                sprintf('Can not get ip range by mask. Reason: Unknown ip version')
            );
        }

        $comparableIpRange = [
            "first" => $this->_convertIpToComparableString($fromIp),
            "last" => $this->_convertIpToComparableString($toIp)
        ];

        return $comparableIpRange;
    }

    /**
     * Диапазон по подмаскам
     *
     * Формат настройки:
     * 1. IPv4/xx, где xx это число с 0 - 32
     * 2. IPv6/xxx, где xxx это число с 0 - 128
     *
     * @param array $maskRange
     * @return array
     * @throws \Exception
     */
    protected function getIpRangeBySubMask($maskRange)
    {
        $ip = $maskRange[0];
        $range = $maskRange[1];

        $ipVersion = $this->getIpVersionBy($ip);

        $mask = $this->getMask($ipVersion, $range);

        $ipBin = $this->ipToBinary($ip);
        $maskBin = $this->ipToBinary($mask);

        $first = $this->binaryToIp($ipBin & (~$maskBin));
        $last = $this->binaryToIp($ipBin & (~$maskBin) | $maskBin);

        $comparableIpRange = [
            "first" => $this->_convertIpToComparableString($first),
            "last" => $this->_convertIpToComparableString($last)
        ];

        return $comparableIpRange;
    }

    /**
     * Получаем максу для конкретной версии IP
     *
     * @param string $ipVersion
     * @param int $range
     * @return string
     * @throws \Exception
     */
    public function getMask($ipVersion, $range = 0)
    {
        switch ($ipVersion) {
            case self::IPV4:
                return $this->getIpVFourMask($range);
            case self::IPV6:
                return $this->getIpVSixMask($range);
            default:
                throw new \Exception('Can not create mask');
        }
    }

    /**
     * Генерируется маска для IP версии 4
     *
     * @param string $ipVersion
     * @param int $range
     * @return string
     */
    protected function getIpVFourMask($range = 0)
    {
        $size = $this->getMaskSize(self::IPV4);

        $ipBinary = '';

        for ($bits = $size - $range; $bits; $bits--) {
            $ipBinary .= "1";
        }

        $ipBinary = str_pad($ipBinary, $size, "0", STR_PAD_LEFT);
        $ipBinaryParts = str_split($ipBinary, 8);

        foreach ($ipBinaryParts as $key => $part) {
            $ipBinaryParts[$key] = bindec($part);
        }

        $mask = implode('.', $ipBinaryParts);

        return $mask;
    }

    /**
     * Размер IP адресса исходля из версии адреса
     *
     * 1. IPv4 = 32 bit
     * 2. IPv6 = 128 bit
     *
     * @param $ipVersion
     * @return int
     */
    protected function getMaskSize($ipVersion)
    {
        switch ($ipVersion) {
            case self::IPV4:
                return 32;
            case self::IPV6:
                return 128;
        }
        //default mask size for IPv4
        return 32;
    }

    /**
     * Генерируется маска для IP версии 6
     *
     * @param int $range
     * @return string
     */
    protected function getIpVSixMask($range)
    {
        $size = $this->getMaskSize(self::IPV6);

        $ipBinary = '';
        for ($bits = $size - $range; $bits > 0; $bits--) {
            $ipBinary .= "1";
        }

        $ipBinary = str_pad($ipBinary, $size, "0", STR_PAD_LEFT);
        $ipBinaryParts = str_split($ipBinary, 16);

        foreach ($ipBinaryParts as $key => $part) {
            $ipBinaryParts[$key] = dechex(bindec($part));
        }

        $mask = implode(':', $ipBinaryParts);

        return $mask;
    }

    /**
     * IP address conversion to binary for subsequent comparison
     *
     * @param string $ip
     * @return string $bin
     * @throws \Exception
     */
    protected function ipToBinary($ip)
    {
        $keyCode = "A";
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $keyCode = "a";
        }
        switch ($this->getIpVersionBy($ip)) {
            case self::IPV4:
                return current(unpack($keyCode . "4", inet_pton($ip)));
            case self::IPV6:
                return current(unpack($keyCode . "16", inet_pton($ip)));
            default:
                throw new \Exception("Please supply a valid IPv4 or IPv6 address");

        }
    }

    /**
     * IP address conversion from binary to a standard IP address
     *
     * @param string $binaryString
     * @return string
     * @throws \Exception
     */
    protected function binaryToIp($binaryString)
    {
        $keyCode = "A";
        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $keyCode = "a";
        }

        if (strlen($binaryString) == 16 || strlen($binaryString) == 4) {
            return inet_ntop(pack($keyCode . strlen($binaryString), $binaryString));
        }

        throw new \Exception("Please provide a 4 or 16 byte string");
    }

    /**
     * @param string $ipRange
     * @return array
     * @throws \Exception
     */
    protected function _convertIpVSixStringToIpRange($ipRange)
    {
        $ip = explode("|", $ipRange);
        $ip = trim($ip[0]);
        $simpleRange = explode("-", $ip);

        // for xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx-yyyy:yyyy:yyyy:yyyy:yyyy:yyyy:yyyy-yyyy
        if (count($simpleRange) == 2) {
            return $this->getIpRangeBy($simpleRange);
        }

        // for xxxx:xxxx:xxxx:xxxx:xxxx:*:*:*
        return $this->_processIpPattern($ip);
    }

    /**
     * check Access By Token
     *
     * @param $request
     * @return bool
     * @throws LocalizedException
     */
    protected function _checkSecurityTokenAccess()
    {
        $this->getHelper()->log('_checkSecurityTokenAccess()');

        $access = false;

        // if Module Enabled && Not Empty Url and Token
        if (($this->getHelper()->isEnabledIpSecurityToken()) && ($this->getHelper()->isSetTokenLastUpdateAndUrl())) {
            $this->getHelper()->log('IpSecurityToken: Enabled');

            $tokenModel = $this->configManager;

            if (!$tokenModel->isTokenExpired()) {
                $this->getHelper()->log('token not expired');

                $tokenValueConfig = $this->getHelper()->getTokenValue();

                $access = $this->_checkAccessByCookie($tokenValueConfig);

                if (!$access) {
                    $access = $this->_checkAccessByToken($tokenValueConfig);
                }
            } else {
                // log token expired
                $this->getHelper()->log('token expired');
            }
        } else {
            $this->getHelper()->log('IpSecurityToken: Disabled');
        }

        return $access;
    }

    /**
     * check access By cookie
     * is set & valid return true
     *
     * @param string $tokenValueConfig
     * @return bool
     */
    protected function _checkAccessByCookie($tokenValueConfig)
    {
        $this->getHelper()->log('_checkAccessByCookie()');
        $access = false;

        $cookieValue = $this->getHelper()->getCookie(self::TOKEN_COOKIE_NAME);

        $this->getHelper()->log('cookie value: ');
        $this->getHelper()->log($cookieValue);
        $this->getHelper()->log('token from config: ');
        $this->getHelper()->log($tokenValueConfig);

        // check cookie if OK set new Time Expire
        if ($cookieValue) {
            if ($cookieValue == $tokenValueConfig) {
                $this->getHelper()->setCookieToken(self::TOKEN_COOKIE_NAME, $cookieValue);
                $access = true;

                // log cookie update
                $this->getHelper()->log('cookie valid & update, access: true');
            } else {
                // cookie not valid
                $this->getHelper()->log('cookie not valid, access: false');
            }
        } else {
            $this->getHelper()->log('cookie not set');
        }

        return $access;
    }

    /**
     * @param string $tokenValueConfig
     * @return bool
     * @throws LocalizedException
     */
    protected function _checkAccessByToken($tokenValueConfig)
    {
        $this->getHelper()->log('_checkAccessByToken()');

        $access = false;

        $tokenName = $this->getHelper()->getTokenName();
        $this->getHelper()->log('token Name: ' . $tokenName);

        $tokenValueRequest = $this->phpRequest->getParam($tokenName);

        //$fullUrl = $controller->getRequest()->getServer('HTTP_REFERER');
        //$fullUrl = $controller->getRequest()->getServer('SCRIPT_URI');
        $fullUrl = $this->url->getCurrentUrl();

        $this->getHelper()->log('token value request: ' . $tokenValueRequest);
        $this->getHelper()->log('token value config: ' . $tokenValueConfig);

        if ($tokenValueRequest) {
            if ($tokenValueRequest == $tokenValueConfig) {
                $this->getHelper()->setCookieToken(self::TOKEN_COOKIE_NAME, $tokenValueConfig);
                $access = true;

                if (!self::$_flagCheckToken) {
                    $this->_addTokenLog($fullUrl, 'Successful token use');

                    $this->_notifyLoginByToken($fullUrl, true);

                    // log logOn By token Ok
                    $this->getHelper()->log('Successful token use: Ok, set cookie Ok');

                    self::$_flagCheckToken = 1;
                }
            } else {
                // log not valid token
                $this->getHelper()->log('Unsuccessful token use attempt: not valid token');

                $this->_addTokenLog($fullUrl, 'Unsuccessful token use attempt');

                if ($this->_alwaysNotifyToken) {
                    $this->_notifyLoginByToken($fullUrl, false);
                }
            }
        }

        return $access;
    }

    /**
     * convert array to string
     *
     * @param array $arrayOfIp
     * @return string
     */
    protected function _arrayIpToString($arrayOfIp)
    {
        $blockedIps = '';
        if (is_array($arrayOfIp)) {
            foreach ($arrayOfIp as $protocol => $ip) {
                $blockedIps .= $protocol . ' : ' . $ip . ' ';
            }
        } else {
            $blockedIps = $arrayOfIp;
        }
        return $blockedIps;
    }

    /**
     * add token Log
     *
     * @param $fullUrl
     * @param string $message
     */
    protected function _addTokenLog($fullUrl, $message)
    {
        /** @var \RedChamps\IpSecurity\Model\Iptokenlog $ipTokenLogModel */
        $ipTokenLogModel = $this->ipSecurityTokenLogFactory->create();

        $ips = $this->getCurrentIp();
        $blockedIps = $this->_arrayIpToString($ips);

        $ipTokenLogModel->setData('blocked_ip', $blockedIps);

        $ipTokenLogModel->setData(
            'last_block_rule',
            $message
        );

        $ipTokenLogModel->setData('create_time', $this->timeZone->formatDate(
            null,
            'Y-m-d H:i:s',
            true
        ));

        $this->getHelper()->log('_addTokenLog():');
        $this->getHelper()->log('url: ' . $fullUrl);

        $ipTokenLogModel->setData('blocked_from', $fullUrl);

        try {
            $ipTokenLogModel->save();
        } catch (\Exception $ex) {
            $this->getHelper()->log('error Add Token Log: ', $ex->getMessage());
        }
    }

    /**
     * send Token email notification
     *
     * @param bool $success
     * @throws LocalizedException
     */
    protected function _notifyLoginByToken($fullUrl, $success)
    {
        $this->getHelper()->log('_notifyLoginByToken()');

        if ($success) {
            $template = $this->_emailTemplateToken;
        } else {
            $template = $this->_emailTemplateTokenFail;
        }

        if (!$this->_eventEmailToken && (!$template)) {
            return;
        }

        $currentIp = $this->getCurrentIp();
        $currentIpString = $this->_arrayIpToString($currentIp);

        $recipients = preg_split('/\r\n|[\r\n]/', $this->_eventEmailToken);

        $vars = [
            'ip' => $currentIpString,
            'ip_rule' => __($this->getLastBlockRule()),
            'date' => $this->timeZone->formatDate(
                null,
                \IntlDateFormatter::FULL,
                true
            ),
            'storetype' => $this->_storeType,
            'url' => $fullUrl,
            'info' => base64_encode(
                $this->serializer->serialize(
                    [
                        $this->_rawAllowIpData,
                        $this->_rawBlockIpData
                    ]
                )
            ),
        ];

        try {
            $this->emailSender->sendEmail($this->_emailIdentity, $template, $recipients, $vars);
        } catch (\Exception $ex) {
            $this->getHelper()->log($ex);
        }
    }

    /**
     * Return block rule
     *
     * @return string
     */
    public function getLastBlockRule()
    {
        $lastBlockRule = 'Not in allowed list';
        if (!is_null($this->_lastFoundIp)) {
            $lastBlockRule = $this->_lastFoundIp;
        }
        return $lastBlockRule;
    }

    /**
     * Redirect denied users to block page or show maintenance page to visitor
     *
     * @param $allow boolean
     * @param $currentIp string
     * @param $observer
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _processAllowDeny($allow, $currentIp, $observer)
    {
        $currentPage = $this->trimTrailingSlashes($this->url->getCurrentUrl());
        // searching for CMS page storeId
        // (block access to admin redirects to admin)
        $pageStoreId = $this->getPageStoreId();
        if ($pageStoreId !== false) {
            $this->_redirectPage = $this->url->getUrl(
                null,
                ['_direct' => $this->_redirectPage, "_store" => $pageStoreId]
            );
        }
        $scope = $this->_getScopeName();

        if (!strlen($this->_redirectPage)) {
            $this->_redirectPage = $this->trimTrailingSlashes($this->url->getUrl('no-route'));
        }

        if ($this->_redirectBlank == 1 && !$allow) {
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            header("Content-type: text/html");
            $needToNotify = $this->saveToLog(['blocked_from' => $scope, 'blocked_ip' => $currentIp]);
            if (($this->_alwaysNotify) || $needToNotify) {
                $this->_send();
            }
            exit("Access denied for IP:<b> " . $currentIp . "</b>");
        }

        if ($this->trimTrailingSlashes($currentPage) != $this->trimTrailingSlashes($this->_redirectPage) && !$allow) {
            header('Location: ' . $this->_redirectPage);
            $needToNotify = $this->saveToLog(['blocked_from' => $scope, 'blocked_ip' => $currentIp]);
            if (($this->_alwaysNotify) || $needToNotify) {
                $this->_send();
            }
            exit();
        }

        $exceptIps = $this->_ipTextToArray($this->_rawExceptIpData);
        $isMaintenanceMode = $this->configManager->readConfig('maintenance/enabled');
        if (($isMaintenanceMode) && ($this->_isFrontend)) {
            $doNotLoadSite = true;
            # look for except
            if ($exceptIps) {
                # are there any except ips
                if ($this->isIpInList($currentIp, $exceptIps)) {
                    $observer->getControllerAction()->getResponse()->appendBody(
                        html_entity_decode(
                            $this->configManager->readConfig('maintenance/remindermessage'),
                            ENT_QUOTES,
                            "utf-8"
                        )
                    );
                    $doNotLoadSite = false;
                }
            }

            //custom change to allow token access
            if ($this->_checkSecurityTokenAccess()) {
                $doNotLoadSite = false;
            }

            if ($doNotLoadSite) {
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                header('Retry-After: 7200'); // in seconds
                print html_entity_decode(
                    $this->configManager->readConfig('maintenance/message'),
                    ENT_QUOTES,
                    "utf-8"
                );
                exit();
            }
        }
    }

    /**
     * Get store id of target redirect cms page
     *
     * @return int|
     */

    public function getPageStoreId()
    {
        $cmsPage = $this->cmsPageFactory->create();
        $storeId = $this->storeManager->getStore()->getId();

        //if current store is Admin
        if ($storeId == 0) {
            if (!empty($this->phpRequest->getServer("SERVER_NAME"))) {
                /** @var \Magento\Store\Model\Store $store */
                foreach ($this->storeManager->getStores() as $store) {
                    $url = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, false);
                    //domain check
                    if (strpos($url, $this->phpRequest->getServer("SERVER_NAME")) !== false) {
                        $redirectPage = $this->trimTrailingSlashes(
                            $this->configManager->readConfig('admin/redirect_page')
                        );
                        //store have that page
                        if ($cmsPage->checkIdentifier($redirectPage, $store->getId())) {
                            $this->_redirectPage = $redirectPage;
                            return $store->getId();
                        }
                    }
                }
            }
        }
        //check identifier check page on active and specified store
        $pageId = $cmsPage->checkIdentifier($this->_redirectPage, $storeId);
        if ($pageId > 0) {
            //current store id
            return $storeId;
        }
        //no active redirect page for current store
        return false;
    }

    /**
     * Get current Scope (frontend, admin)
     *
     * @return string
     */
    protected function _getScopeName()
    {
        if ($this->_isFrontend) {
            $scope = 'frontend';
        } else {
            $scope = 'admin';
        }

        return $scope;
    }

    /**
     * Save Blocked IP to log
     *
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    protected function saveToLog($params = [])
    {
        $needNotify = true;

        $ips = $this->getCurrentIp();
        $ip = $this->_arrayIpToString($ips);

        if (!((isset($params['blocked_ip'])) && (strlen(trim($params['blocked_ip'])) > 0))) {
            $params['blocked_ip'] = $ip;
        }

        if (!((isset($params['blocked_from'])) && (strlen(trim($params['blocked_from'])) > 0))) {
            $params['blocked_from'] = 'undefined';
        }

        $now = $this->timeZone->formatDate(
            null,
            'Y-m-d H:i:s',
            true
        );

        /* @var $logTable RedChamps_IpSecurity_Model_ResourceModel_Ipsecuritylog_Collection */
        $logTable = $this->ipSecurityLogCollectionFactory->create();
        $logTable->getSelect()->where('blocked_from=?', $params['blocked_from'])
            ->where('blocked_ip=?', $params['blocked_ip']);

        if (count($logTable) > 0) {
            foreach ($logTable as $row) {
                /* @var $row RedChamps_IpSecurity_Model_Ipsecuritylog */
                $timesBlocked = $row->getData('qty') + 1;
                $row->setData('qty', $timesBlocked);
                $row->setData('last_block_rule', $this->getLastBlockRule());
                $row->setData('update_time', $now);
                $row->save();
                if (($timesBlocked % 10) == 0) {
                    $needNotify = true;
                } else {
                    $needNotify = false;
                }
            }
        } else {
            /** @var \RedChamps\IpSecurity\Model\Ipsecuritylog $log */
            $log = $this->ipSecurityLogFactory->create();

            $log->setData('blocked_from', $params['blocked_from']);
            $log->setData('blocked_ip', $params['blocked_ip']);
            $log->setData('qty', '1');
            $log->setData('last_block_rule', $this->getLastBlockRule());
            $log->setData('create_time', $now);
            $log->setData('update_time', $now);

            $log->save();
            $needNotify = true;
        }

        // if returns true - IP blocked for first time or timesBloked is 10, 20, 30 etc.
        return $needNotify;
    }

    /**
     * Send to admin information about IP blocking
     */
    protected function _send()
    {
        $sendResult = false;
        if (!$this->_eventEmail) {
            return $sendResult;
        }

        $currentIp = $this->getCurrentIp();
        $ip = $this->_arrayIpToString($currentIp);
        //$storeId = 0; //admin

        $recipients = preg_split('/\r\n|[\r\n]/', $this->_eventEmail);

        $vars = [
            'ip' => $ip,
            'ip_rule' => __($this->getLastBlockRule()),
            'date' => $this->timeZone->formatDate(null, \IntlDateFormatter::FULL, true),
            'storetype' => $this->_storeType,
            'url' => $this->url->getCurrentUrl(),
            'info' => base64_encode($this->serializer->serialize([$this->_rawAllowIpData, $this->_rawBlockIpData])),
        ];
        try {
            $this->emailSender->sendEmail($this->_emailIdentity, $this->_emailTemplate, $recipients, $vars);
            $sendResult = true;
        } catch (\Exception $ex) {
            $this->getHelper()->log($ex);
        }
        return $sendResult;
    }

    /**
     * @param $ip
     * @return array
     * @throws \Exception
     */
    protected function _processIpPattern($ip)
    {
        if (strpos($ip, "*") !== false) {
            $comparableIpRange = $this->getIpRangeByMask($ip);
            return $comparableIpRange;
        }
        //for xx.xx.xx.xx/yy
        $maskRange = explode("/", $ip);
        if (count($maskRange) == 2) {
            $comparableIpRange = $this->getIpRangeBySubMask($maskRange);
            return $comparableIpRange;
        }

        //for xx.xx.xx.xx
        $comparableIpRange = [
            "first" => $this->_convertIpToComparableString($ip),
            "last" => $this->_convertIpToComparableString($ip)
        ];

        return $comparableIpRange;
    }
}
