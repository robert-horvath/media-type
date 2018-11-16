<?php
declare(strict_types = 1);
namespace RHo\MediaType;

// https://tools.ietf.org/html/rfc6838#section-4.2
// https://tools.ietf.org/html/rfc5234#appendix-B.1
// https://tools.ietf.org/html/rfc822#section-3.3
class MediaTypeFactory implements MediaTypeFactoryInterface
{

    // CHAR = %x00-7F ; Any ASCII character
    private const CHAR = '\0-\x7F';

    // ALPHA = %x41-5A / %x61-7A ; Upper- and lower-case ASCII letters (A–Z, a–z)
    private const ALPHA = 'a-zA-Z';

    // DIGIT = %x30-39 ; Decimal digits (0–9)
    private const DIGIT = '0-9';

    // CTL = %x00-1F / %x7F ; Controls, Any ASCII control character and DEL
    private const CTL = '\0-\x1F\x7F';

    // CR = %x0D ; Carriage return
    private const CR = '\r';

    // LF = %x0A ; Linefeed
    private const LF = '\n';

    // SP = %x20 ; Space
    private const SP = ' ';

    // HTAB = %x09 ; Horizontal tab
    private const HTAB = '\t';

    // DQUOTE = %x22 ; Double quote
    private const DQUOTE = '\"';

    // CRLF = CR LF ; Internet-standard newline
    private const CRLF = self::CR . self::LF;

    // LWSP-char = SPACE / HTAB ; semantics = SPACE
    private const LWSP_char = self::SP . self::HTAB;

    // linear-white-space = 1*([CRLF] LWSP-char) ; semantics = SPACE CRLF => folding
    public const linear_white_space = '(?:(?:' . self::CRLF . ')?[' . self::LWSP_char . '])+';

    // specials = "(" / ")" / "<" / ">" / "@" / "," / ";" / ":" / "\" / DQUOTE / "." / "[" / "]" ; Must be in quoted-string, to use within a word.
    public const specials = '()<>@,;:\x5C\.\[\]' . self::DQUOTE;

    // tspecials = "(" / ")" / "<" / ">" / "@" / "," / ";" / ":" / "\" / DQUOTE / "/" / "[" / "]" / "?" / "=" ; Must be in quoted-string, to use within parameter values
    public const tspecials = '()<>@,;:\x5C\[\]?=\/' . self::DQUOTE;

    // token = %x21%x23-27%x2A-2B-%x2D-2E%x30-39%x41-5A%5E-7E ; 1*<any (US-ASCII) CHAR except SPACE, CTLs, or tspecials>
    public const token = '[!#-\'*+\-.0-9A-Z\^-~' . self::DQUOTE . ']+';

    // qtext = <any CHAR excepting DQUOTE, "\" & CR, and including linear-white-space> ; => may be folded
    private const qtext = '(?:[\00-\x0C\x0E-\x21\x23-\x5B\x5D-\x7F]|' . self::linear_white_space . ')';

    // quoted-pair = "\" CHAR ; may quote any char
    private const quoted_pair = '\x5C[' . self::CHAR . ']';

    // quoted-string = DQUOTE *(qtext/quoted-pair) DQUOTE; Regular qtext or quoted chars.
    public const quoted_string = '"(?:' . self::qtext . '|' . self::quoted_pair . ')*"';

    // type-name = restricted-name
    private const type_name = self::restricted_name;

    // subtype-name = restricted-name
    private const subtype_name = self::restricted_name;

    // restricted-name = restricted-name-first *126restricted-name-chars
    private const restricted_name = '(' . self::restricted_name_first . self::restricted_name_chars . '{0,126})';

    // restricted-name-first = ALPHA / DIGIT
    private const restricted_name_first = '[' . self::ALPHA . self::DIGIT . ']';

    // restricted-name-chars = ALPHA / DIGIT / "!" / "#" / "$" / "&" / "-" / "^" / "_"
    // restricted-name-chars =/ "." ; Characters before first dot always specify a facet name
    // restricted-name-chars =/ "+" ; Characters after last plus always specify a structured syntax suffix
    private const restricted_name_chars = '[' . self::ALPHA . self::DIGIT . '!#$&.+\-^_' . ']';

    // parameter := attribute "=" value
    private const parameter = self::attribute . '=(?:' . self::value . ')';

    // attribute := token ; Matching of attributes is ALWAYS case-insensitive.
    private const attribute = self::token;

    // value := token / quoted-string
    private const value = self::token . '|' . self::quoted_string;

    // content := "Content-Type" ":" type "/" subtype *(";" parameter) ; Matching of media type and subtype is ALWAYS case-insensitive.
    public const content = self::type_name . '\/' . self::subtype_name . '((?:;' . self::parameter . ')*)';

    // qvalue = ( "0" [ "." 0*3DIGIT ] ) | ( "1" [ "." 0*3("0") ] )
    private const qvalue = '(?:0(?:\.[' . self::DIGIT . ']{0,3})?)|(?:1(?:\.[0]{0,3})?)';

    /** @var array */
    private $mediaTypes;

    public function build(): ?array
    {
        $arr = [];
        foreach ($this->mediaTypes as $mt)
            if (($arr[] = $this->createMediaType($mt)) === NULL)
                return NULL;
        return $arr;
    }

    private function createMediaType(string $mediaType): ?MediaTypeInterface
    {
        $matches = NULL;
        $result = $this->pregMatchMediaType($mediaType, $matches);
        if ($result !== 1)
            return NULL;
        return $this->initMediaType($matches[1], $matches[2], $this->parseParameters($matches[3] ?? ''));
    }

    private function parseParameters(string $data): array
    {
        $parameters = $arr = [];
        parse_str(str_replace(';', '&', $data), $parameters);
        foreach ($parameters as $key => $value)
            $arr[$key] = $value;
        return $arr;
    }

    private function initMediaType(string $type, string $subType, array $params): ?MediaTypeInterface
    {
        if (! $this->validateParameterQ($params['q'] ?? '1'))
            return NULL;
        return new MediaType($type, $subType, $params);
    }

    private function validateParameterQ(string $q): bool
    {
        $matches = NULL;
        if ($this->pregMatchParameterQ($q, $matches) === 1)
            return TRUE;
        return FALSE;
    }

    private function pregMatchMediaType(string $mediaType, ?array &$matches): int
    {
        $pattern = '/^(?:' . self::content . '){1,128}$/';
        return $this->pregMatch($pattern, $mediaType, $matches);
    }

    private function pregMatchParameterQ(string $q, ?array &$matches): int
    {
        $pattern = '/^(' . self::qvalue . ')$/';
        return $this->pregMatch($pattern, $q, $matches);
    }

    private function pregMatch(string $pattern, string $subject, ?array &$matches): int
    {
        $result = preg_match($pattern, $subject, $matches);
        if ($result === FALSE)
            throw new \RuntimeException('preg_match: regular expression failed', preg_last_error());
        return $result;
    }

    public function fromString(string $mediaTypes): MediaTypeFactoryInterface
    {
        $this->mediaTypes = explode(',', $mediaTypes);
        return $this;
    }
}