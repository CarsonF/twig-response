<?php

declare(strict_types=1);

namespace Gmo\Web\Logger\Handler;

use Bolt\Common\Json;
use Gmo\Web\Logger\Formatter\SlackFormatter;

/**
 * {@inheritdoc}
 *
 * Subclassing to do formatting ourselves.
 */
class SlackHandler extends \Monolog\Handler\SlackHandler
{
    /** @var string */
    private $token;
    /** @var string */
    private $channel;
    /** @var string */
    private $username;

    /**
     * Constructor.
     *
     * @param string     $token    Slack API token
     * @param string     $channel  Slack channel/user (encoded ID or name: #channel or @user)
     * @param string     $username Name of a bot
     */
    public function __construct(
        string $token,
        string $channel,
        string $username = 'Logger'
    ) {
        $this->token = $token;
        $this->channel = $channel;
        $this->username = $username;

        parent::__construct('', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        return new SlackFormatter();
    }

    /**
     * @inheritdoc
     *
     * Ignore all of parent's attempted abstraction/configuration for formatting the data packet and do it ourselves.
     */
    protected function prepareContentData($record)
    {
        $attachment = [
            'text'      => $record['formatted'],
            'fallback'  => $record['message'],
            'mrkdwn_in' => ['text'],
            'color'     => 'danger',
            'fields'    => [],
            'ts'        => $record['datetime']->getTimestamp(),
        ];

        $data = [
            'token'       => $this->token,
            'channel'     => $this->channel,
            'username'    => $this->username,
            'icon_emoji'  => ':page_with_curl:',
            'attachments' => Json::dump([$attachment]),
        ];

        return $data;
    }
}
