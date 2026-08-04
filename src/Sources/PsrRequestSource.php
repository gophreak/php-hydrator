<?php

declare(strict_types=1);

namespace Hydrator\Sources;

use Hydrator\Source;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PsrRequestSource implements Source
{
    public const int PARSE_BODY = 1 << 0;
    public const int PARSE_QUERY = 1 << 1;
    public const int PARSE_ATTRIBUTES = 1 << 2;
    public const int PARSE_DEFAULT = self::PARSE_BODY | self::PARSE_QUERY;

    /**
     * $options is a bitmask of the ALLOW_ constants. This allows the user to choose which parts of the request to use.
     * The default is to allow all parts of the request: Body, Query, and Attributes in priority order.
     * If the source should ignore parts of the request, the user can pass constant(s) to the constructor to select
     * which parts to parse.
     *
     * @example new PsrRequestSource($request, PsrRequestSource::PARSE_BODY) will only parse the body of the request.
     * @example new PsrRequestSource($request, PsrRequestSource::PARSE_BODY | PsrRequestSource::PARSE_QUERY) will parse the body and query of the request.
     * @example new PsrRequestSource($request, PsrRequestSource::PARSE_QUERY) will parse only the query of the request.
     */
    public function __construct(private ServerRequestInterface $request, private int $options = self::PARSE_DEFAULT) {}

    public function has(string $key): bool
    {
        $body = $this->request->getParsedBody();

        if (($this->options & self::PARSE_BODY) && is_array($body) && array_key_exists($key, $body)) {
            return true;
        }

        if (($this->options & self::PARSE_QUERY) && array_key_exists($key, $this->request->getQueryParams())) {
            return true;
        }

        if (($this->options & self::PARSE_ATTRIBUTES) && array_key_exists($key, $this->request->getAttributes())) {
            return true;
        }

        return false;
    }

    public function get(string $key): mixed
    {
        $body = $this->request->getParsedBody();

        if (($this->options & self::PARSE_BODY) && is_array($body) && array_key_exists($key, $body)) {
            return $body[$key];
        }

        $query = $this->request->getQueryParams();

        if (($this->options & self::PARSE_QUERY) && array_key_exists($key, $query)) {
            return $query[$key];
        }

        $attributes = $this->request->getAttributes();

        if (($this->options & self::PARSE_ATTRIBUTES) && array_key_exists($key, $attributes)) {
            return $attributes[$key];
        }

        return null;
    }
}
