<?php
namespace RedChamps\IpSecurity\Model;

use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use RedChamps\IpSecurity\Logger\Logger;

/**
 * Class RedChamps_IpSecurity_Model_ConfigManager
 */
class ConfgManager
{
    const MODULE_NAME = 'ip_security';

    const MESSAGE_TOKEN_NOT_CREATED = 'Token not Created';
    const MESSAGE_TOKEN_NOT_UPDATED = 'Token not Created';

    protected $_frontTokenUrl;

    protected $_adminTokenUrl;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var IpVariableFactory
     */
    protected $ipSecurityIpVariableFactory;

    /**
     * @var Logger
     */
    protected $logger;

    protected $cookieModel;

    protected $configWriter;

    protected $random;

    protected $backendUrl;

    protected $url;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * ConfgManager constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param IpVariableFactory $ipSecurityIpVariableFactory
     * @param Logger $logger
     * @param CookieManagerInterface $cookieModel
     * @param WriterInterface $configWriter
     * @param Random $random
     * @param UrlInterface $backendUrl
     * @param UrlInterface $url
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        IpVariableFactory $ipSecurityIpVariableFactory,
        Logger $logger,
        CookieManagerInterface $cookieModel,
        WriterInterface $configWriter,
        Random $random,
        BackendUrlInterface $backendUrl,
        UrlInterface $url,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->ipSecurityIpVariableFactory = $ipSecurityIpVariableFactory;
        $this->logger = $logger;
        $this->cookieModel = $cookieModel;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->configWriter = $configWriter;
        $this->random = $random;
        $this->backendUrl = $backendUrl;
        $this->url = $url;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $cookieName
     * @return mixed
     */
    public function getCookie($cookieName)
    {
        return $this->cookieModel->getCookie($cookieName);
    }

    /**
     * set cookie token
     *
     * @param string $cookieName
     * @param string $cookieValue
     */
    public function setCookieToken($cookieName, $cookieValue)
    {
        $cookieTime = $this->getCookieExpiredTime();
        $this->setCookie($cookieName, $cookieValue, $cookieTime);
    }

    /**
     * set Cookie Value
     *
     * @param string $cookieName
     * @param string $cookieValue
     * @param string $cookiePeriod
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setCookie($cookieName, $cookieValue, $cookiePeriod)
    {
        $cookieModel = $this->cookieModel;

        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($cookiePeriod)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $cookieModel->setPublicCookie($cookieName, $cookieValue, $metadata);
    }

    /**
     * check is Enabled 'Security Token'
     *
     * @return bool
     */
    public function isEnabledIpSecurityToken()
    {
        return (bool)$this->readConfig('token/enabled');
    }

    /**
     * return count of days
     *
     * @return int
     */
    public function getTokenExpireTime()
    {
        return (int)$this->readConfig('token/validity/token_expire');
    }

    /**
     * return time (hour)
     *
     * @return int
     */
    public function getCookieExpireTime()
    {
        return (int)$this->readConfig('token/validity/cookie_expire');
    }

    /**
     * @return string
     */
    public function getTokenName()
    {
        return (string)$this->readConfig('token/link/param_name');
    }

    /**
     * @return string
     */
    public function getTokenValue()
    {
        return (string)$this->readConfig('token/link/token');
    }

    /**
     * remove token link
     */
    public function resetTokenLinks()
    {
        $this->saveConfigValue('token/link/token', '');
        $this->saveConfigValue('token/link/front', '');
        $this->saveConfigValue('token/link/admin', '');
    }

    /**
     * set Url to admin page with token
     *
     * @param $tokenName
     */
    public function setToken($tokenName)
    {
        $adminUrl = $this->backendUrl->getRouteUrl('adminhtml');
        $frontUrl = $this->url->getBaseUrl();

        $token = '?' . $tokenName . '=';
        $token .= $this->_setToken();

        $adminUrl .= $token;
        $frontUrl .= $token;

        $this->_frontTokenUrl = $frontUrl;
        $this->_adminTokenUrl = $adminUrl;

        $this->saveConfigValue('token/link/admin', $adminUrl);
        $this->saveConfigValue('token/link/front', $frontUrl);
    }

    /**
     * get Url for access to FrontEnd
     *
     * @return string
     */
    public function getFrontTokenUrl()
    {
        if (!$this->_frontTokenUrl) {
            $this->_frontTokenUrl = (string)$this->readConfig('token/link/front');
        }
        return $this->_frontTokenUrl;
    }

    /**
     * get Url for access to FrontEnd
     *
     * @return string
     */
    public function getAdminTokenUrl()
    {
        if (!$this->_adminTokenUrl) {
            $this->_adminTokenUrl = (string)$this->readConfig('token/link/admin');
        }
        return  $this->_adminTokenUrl;
    }

    /**
     * generate token  & save to config
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _setToken()
    {
        $token = hash('sha256', $this->random->getRandomString($length = 32));
        $this->saveConfigValue('token/link/token', $token);
        return $token;
    }

    /**
     * @param string $configPath
     * @param string $value
     */
    public function saveConfigValue($configPath, $value)
    {
        $coreConfig = $this->configWriter;
        $coreConfig->save(
            self::MODULE_NAME . '/' . $configPath,
            $value
        );
    }

    /**
     * get Url to Admin page with token
     *
     * @return string
     */
    public function getToken()
    {
        return (string)$this->readConfig('token/link/token');
    }

    /**
     * remove Last Update Token Time
     */
    public function resetLastUpdateTokenTime()
    {
        $this->saveConfigValue('token/link/last_updated_date', '');
    }

    /**
     * set Date Last Update Token
     *
     * @return string
     */
    public function setLastUpdateToken()
    {
        $date = date('Y-m-d H:i:s');
        $this->saveConfigValue('token/link/last_updated_date', $date);
        return $date;
    }

    /**
     * create Comment Message For Grid of Expired Token Time
     *
     * @return string
     */
    public function getTokenExpiredTimeMessage()
    {
        $msg = '';
        $timeLastUpdateToken = $this->getLastUpdateToken();

        if ($timeLastUpdateToken == '') {
            $msg .= __('Token not created');
        } else {
            if ($this->isTokenExpired()) {
                $msg = __('Token expired!');
            } else {
                $tokenExpiredTimeStamp = $this->getTokenExpiredTimestamp();
                $differentTime = $tokenExpiredTimeStamp - time();

                $differentTimeInHour = round($differentTime / (60 * 60));

                if ($differentTimeInHour) {
                    $msg .= __('Token expires after:');
                    $msg .= ' ';
                    $msg .= __('%1 (hours)', $differentTimeInHour);
                } else {
                    $msg = __('Token expired!');
                }
            }
        }

        $msg = trim($msg);

        return $msg;
    }

    /**
     * check token last Update && url (not empty)
     *
     * @return bool
     */
    public function isSetTokenLastUpdateAndUrl()
    {
        if (($this->getLastUpdateToken() != '') && ($this->getToken() != '')) {
            $this->log('isSetTokenLastUpdateAndUrl(): true');
            return true;
        } else {
            $this->log('isSetTokenLastUpdateAndUrl(): false');
            return false;
        }
    }

    /**
     * get Date Last Update Token
     *
     * @return string
     */
    public function getLastUpdateToken()
    {
        return (string)$this->readConfig('token/link/last_updated_date');
    }

    /**
     * Returns ip method which is selected in admin settings
     *
     * @return mixed
     */
    public function getIpVariable()
    {
        $model = $this->ipSecurityIpVariableFactory->create();
        $ipsArray = $model->getOptionArray();

        $configVariable = $this->readConfig('global_settings/get_ip_method');

        if (!in_array($configVariable, $ipsArray)) {
            $configVariable = 'REMOTE_ADDR';
        }

        return $configVariable;
    }

    /**
     * @param string|array $message
     * @return bool
     */
    public function log($message)
    {
        if ($this->isLogEnabled()) {
            if (is_array($message)) {
                $forLog = [];
                foreach ($message as $answerKey => $answerValue) {
                    $answer = !is_scalar($answerValue) ? print_r($answerValue, true) : $answerValue;
                    $forLog[] = $answerKey . ": " . $answer;
                }
                $forLog[] = '***************************';
                $message = implode("\r\n", $forLog);
            }

            $argumentsCount = func_num_args();
            if ($argumentsCount > 1) {
                $forLog = [$message];
                $forLog[] = "Additional data: ";
                $arguments = func_get_args();
                for ($i = 1; $i < $argumentsCount; $i++) {
                    if (!is_object($arguments[$i])) {
                        $forLog[] = !is_scalar($arguments[$i]) ? print_r($arguments[$i], true) : $arguments[$i];
                    }
                }
                $message = implode("\r\n", $forLog);
            }

            $this->logger->debug($message);
        }
        return true;
    }

    /**
     * check Enabled Logging
     *
     * @return bool
     */
    public function isLogEnabled()
    {
        return (bool)$this->readConfig('global_settings/debug');
    }

    public function readConfig($path)
    {
        return $this->scopeConfig->getValue(self::MODULE_NAME . '/' . $path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * return timestamp(LastTimeUpdate + token time life)
     *
     * @return int
     */
    public function getTokenExpiredTimestamp()
    {
        /** @var ConfgManager $helper */
        $tokenTimeInDays = $this->getTokenExpireTime();

        $tokenLastUpdate = $this->getLastUpdateToken();

        if ($tokenLastUpdate) {
            $tokenLastUpdate = strtotime($tokenLastUpdate);
        }

        return $tokenLastUpdate + 60 * 60 * 24 * $tokenTimeInDays;
    }

    /**
     * @return bool
     */
    public function isTokenExpired()
    {
        if (time() > $this->getTokenExpiredTimestamp()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * return timestamp + cookie time life
     *
     * @return int
     */
    public function getCookieExpiredTime()
    {
        $cookieTimeInDays = $this->getCookieExpireTime();
        return 60 * 60 * $cookieTimeInDays;
    }
}
