<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the commercial license
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @category Extait
 * @package Extait_Attachment
 * @copyright Copyright (c) 2016-2018 Extait, Inc. (http://www.extait.com)
 */

namespace Extait\Attachment\Mail;

use Zend\Mime\Mime;
use Zend\Mime\PartFactory;
use Zend\Mail\MessageFactory as MailMessageFactory;
use Zend\Mime\MessageFactory as MimeMessageFactory;

class Message extends \Magento\Framework\Mail\Message
{
    /**
     * @var \Zend\Mime\PartFactory
     */
    protected $partFactory;

    /**
     * @var \Zend\Mime\MessageFactory
     */
    protected $mimeMessageFactory;

    /**
     * @var \Zend\Mail\Message
     */
    private $zendMessage;

    /**
     * @var \Zend\Mime\Part[]
     */
    protected $attachments = [];

    /**
     * Message type
     *
     * @var string
     */
    private $messageType = self::TYPE_TEXT;

    /**
     * Message constructor.
     *
     * @param \Zend\Mime\PartFactory $partFactory
     * @param \Zend\Mime\MessageFactory $mimeMessageFactory
     * @param string $charset
     */
    public function __construct(PartFactory $partFactory, MimeMessageFactory $mimeMessageFactory, $charset = 'utf-8')
    {
        $this->partFactory = $partFactory;
        $this->mimeMessageFactory = $mimeMessageFactory;
        $this->zendMessage = MailMessageFactory::getInstance();
        $this->zendMessage->setEncoding($charset);
    }


    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see \Magento\Framework\Mail\Message::setBodyText
     * @see \Magento\Framework\Mail\Message::setBodyHtml
     */
    public function setMessageType($type)
    {
        $this->messageType = $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     * @see \Magento\Framework\Mail\Message::setBodyText
     * @see \Magento\Framework\Mail\Message::setBodyHtml
     */
    public function setBody($body)
    {
        if (is_string($body) && $this->messageType === \Magento\Framework\Mail\MailMessageInterface::TYPE_HTML) {
            $body = $this->createHtmlMimeFromString($body);
        }
        $this->zendMessage->setBody($body);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        $this->zendMessage->setSubject($subject);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject()
    {
        return $this->zendMessage->getSubject();
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->zendMessage->getBody();
    }

    /**
     * @inheritdoc
     */
    public function setFrom($fromAddress)
    {
        $this->setFromAddress($fromAddress, null);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setFromAddress($fromAddress, $fromName = null)
    {
        $this->zendMessage->setFrom($fromAddress, $fromName);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addTo($toAddress)
    {
        $this->zendMessage->addTo($toAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addCc($ccAddress)
    {
        $this->zendMessage->addCc($ccAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBcc($bccAddress)
    {
        $this->zendMessage->addBcc($bccAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyToAddress)
    {
        $this->zendMessage->setReplyTo($replyToAddress);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRawMessage()
    {
        return $this->zendMessage->toString();
    }

    /**
     * @inheritdoc
     */
    public function setBodyHtml($html)
    {
        $this->setMessageType(self::TYPE_HTML);
        return $this->setBody($html);
    }

    /**
     * @inheritdoc
     */
    public function setBodyText($text)
    {
        $this->setMessageType(self::TYPE_TEXT);
        return $this->setBody($text);
    }

    /**
     * Create HTML mime message from the string.
     *
     * @param string $htmlBody
     * @return \Zend\Mime\Message
     */
    private function createHtmlMimeFromString($htmlBody)
    {
        $htmlPart = $this->partFactory->create(["content" => $htmlBody]);
        $htmlPart->setCharset($this->zendMessage->getEncoding());
        $htmlPart->setType(Mime::TYPE_HTML);
        $mimeMessage = $this->mimeMessageFactory->create();
        $mimeMessage->addPart($htmlPart);
        return $mimeMessage;
    }

    /**
     * Add the attachment mime part to the message.
     *
     * @param string $content
     * @param string $fileName
     * @param string $fileType
     * @return $this
     */
    public function addBodyAttachment($content, $fileName, $fileType)
    {
        $attachmentPart = $this->partFactory->create();

        $attachmentPart->setContent($content)
            ->setType($fileType)
            ->setFileName($fileName)
            ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
            ->setEncoding(Mime::ENCODING_BASE64);

        $this->attachments[] = $attachmentPart;

        return $this;
    }

    /**
     * Set parts to Zend message body.
     *
     * @return $this
     */
    public function addPartsToBody()
    {
        if ($this->attachments) {
            $mimeMessage = $this->zendMessage->getBody();
            foreach ($this->attachments as $attachment) {
                $mimeMessage->addPart($attachment);
            }
            $this->zendMessage->setBody($mimeMessage);
        }
        return $this;
    }
}