<?php declare(strict_types=1);

namespace Gmo\Web\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * A transparent pixel response
 */
class PixelResponse extends Response
{
    /**
     * Base64 encoded transparent pixel
     */
    const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function __construct()
    {
        parent::__construct(null);

        $this->content = base64_decode(static::PIXEL);
        $this->headers->set('Content-Type', 'image/gif');
    }

    public function setContent($content): void
    {
        if ($content !== null) {
            throw new \LogicException('The content cannot be set on a PixelResponse instance.');
        }
    }
}
