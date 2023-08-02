<?php
namespace RedChamps\IpSecurity\Model;

use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{
    protected $storeManager;

    protected $transportBuilder;

    public function __construct(
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
    }

    public function sendEmail($sender, $template, $recipients, $vars)
    {
        $store = $this->storeManager->getDefaultStoreView();
        $storeId = $store->getId();

        $mailer = $this->transportBuilder;

        if (!empty($recipients)) {
            foreach ($recipients as $email) {
                $mailer->addTo($email);
            }

            $mailer->setTemplateOptions(
                [
                    'area'  => Area::AREA_ADMINHTML,
                    'store' => $storeId
                ]
            );

            // Set all required params and send emails
            $mailer->setFromByScope($sender, $storeId);

            $mailer->setTemplateIdentifier($template);

            $mailer->setTemplateVars(
                $vars
            );

            $transport = $mailer->getTransport();

            $transport->sendMessage();
        }

        return $this;
    }
}
