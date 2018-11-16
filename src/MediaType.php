<?php
declare(strict_types = 1);
namespace RHo\MediaType;

final class MediaType implements MediaTypeInterface
{

    /** @var string */
    private $type;

    /** @var string */
    private $subType;

    /** @var string|NULL  Structured syntax suffix */
    private $suffix;

    /** @var array */
    private $parameters;

    /** @var int */
    private $parameterQ;

    public function __construct(string $mainType, string $subType, array $parameters = [])
    {
        $this->type = $mainType;
        $this->subType = $subType;
        $this->suffix = $this->getStructuredSyntaxSuffix();
        $this->parameters = $parameters;
        $this->parameterQ = (int) (1000 * (float) ($this->parameter('q') ?? '1'));
    }

    public function parameter(string $key): ?string
    {
        if (isset($this->parameters[$key]))
            return $this->parameters[$key];
        return NULL;
    }

    public function parameterQ(): int
    {
        return $this->parameterQ;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function subType(): string
    {
        return $this->subType;
    }

    public function structuredSyntaxSuffix(): ?string
    {
        return $this->suffix;
    }

    public function compareTo(MediaTypeInterface $mediaType): int
    {
        if (($i = $this->optionalStrCmp($this->type, $mediaType->type())))
            return $i;
        if (($i = $this->optionalStrCmp($this->subType, $mediaType->subType())))
            return $i;
        foreach ($this->parameters as $k => $v)
            if ($k !== 'q' && ($i = $this->optionalStrCmp($v, $mediaType->parameter($k))))
                return $i;
        return 0;
    }

    private function optionalStrCmp(string $s1, ?string $s2): int
    {
        if ($s2 === NULL)
            return 1;
        return strcmp($s1, $s2);
    }

    public function __toString(): string
    {
        $str = $this->type . '/' . $this->subType;
        if (count($this->parameters) > 0)
            $str = $str . ';' . $this->implodeKeyValueArray($this->parameters);
        return $str;
    }

    private function implodeKeyValueArray(array $input): string
    {
        return implode(';', array_map(function ($v, $k) {
            return sprintf("%s=%s", $k, $v);
        }, $input, array_keys($input)));
    }

    private function getStructuredSyntaxSuffix(): ?string
    {
        $parts = explode('+', $this->subType);
        if (count($parts) == 1)
            return NULL;
        return array_pop($parts);
    }
}