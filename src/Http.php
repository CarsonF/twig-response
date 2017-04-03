<?php declare(strict_types=1);

namespace Gmo\Web;

use Symfony\Component\HttpFoundation\Request;

/**
 * Does this class return a different IP than `Request::getClientIp`?
 * I think they are the same and that should be used instead.
 */
class Http
{
    /**
     * A list of HTTP headers to choose the original client IP address from. In
     * addition to these the RemoteAddr (REMOTE_ADDR) is also used as a final
     * fallback.
     */
    private const HEADERS_TO_CHECK = [
        'x-forwarded-for',
        'client-ip',
        'x-client-ip',
        'rlnclientipaddr',    // from f5 load balancers
        'proxy-client-ip',
        'wl-proxy-client-ip', // weblogic load balancers
        'x-forwarded',
        'forwarded-for',
        'forwarded',
    ];

    /**
     * Get the most suitable IP address from the given request. This function
     * checks the headers defined in {@see Http::HEADERS_TO_CHECK}.
     *
     * @param Request $request Optional request to pull headers from
     *
     * @return null|string The found IP address or null if no IP found.
     */
    public static function getIp(Request $request): ?string
    {
        foreach (static::HEADERS_TO_CHECK as $headerName) {
            if ($request->headers->has($headerName)) {
                $ip = static::extractIp($headerName, $request->headers->get($headerName));
                if ($ip) {
                    return $ip;
                }
            }
        }

        return $request->server->get('REMOTE_ADDR');
    }

    /**
     * Extracts and cleans an IP address from the headerValue. Some headers such
     * as "X-Forwarded-For" can contain multiple IP addresses such as:
     * clientIP, proxy1, proxy2...
     *
     * This method splits up the headerValue and takes the most appropriate
     * value as the IP.
     *
     * @param string $headerName
     * @param string $headerValue
     *
     * @return null|string
     */
    private static function extractIp(string $headerName, $headerValue): ?string
    {
        if ($headerValue === null) {
            return null;
        }
        // "X-Forwarded-For" header can contain many comma separated IPs such as clientIP, proxy1, proxy2...
        // we are interested in the first item
        if ($headerName === 'x-forwarded-for') {
            // loop over all parts and take the first non-empty value
            foreach (explode(',', $headerValue) as $part) {
                $part = trim($part);
                if (static::isPublicIp($part)) {
                    return $part;
                }
            }
        }

        $headerValue = trim($headerValue);
        if (static::isPublicIp($headerValue)) {
            return $headerValue;
        }

        return null;
    }

    /**
     * An IP address is considered public if it is not in any of the following
     * ranges:
     *<pre>
     *  1) Any local address
     *     IP:  0
     *
     *  2) A local loopback address
     *     range:  127/8
     *
     *  3) A site local address i.e. IP is in any of the ranges:
     *     range:  10/8
     *     range:  172.16/12
     *     range:  192.168/16
     *
     *  4) A link local address
     *     range:  169.254/16
     *</pre>
     *
     * @param string $ip The IP address to check
     *
     * @return bool True if it is a public IP, false if the IP is invalid or is not public.
     */
    public static function isPublicIp(string $ip): bool
    {
        $ip = ip2long($ip);
        return (0             !== ($ip & 4294967295))  // 0
               && (2130706432 !== ($ip & 4278190080))  // 127/8
               && (3232235520 !== ($ip & 4294901760))  // 192.168/16
               && (2886729728 !== ($ip & 4293918720))  // 172.16/12
               && (167772160  !== ($ip & 4278190080))  // 10/8
               && (2851995648 !== ($ip & 4294901760)); // 169.254/16
    }
}
