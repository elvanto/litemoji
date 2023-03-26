<?php

namespace LitEmoji;

class Tokenizer
{
    private array $source;
    private string $encoding;
    private string $offset;

    public function __construct(string $source)
    {
        $this->source = mb_str_split($source);
        $this->encoding = mb_detect_encoding($source);
        $this->offset = -1;
    }

    /**
     * Consumes the next multibyte character, returning
     * its codepoint, or null if the end of the source has
     * been reached.
     *
     * @return string|null
     */
    public function consume(): ?string
    {
        if (++$this->offset >= count($this->source)) {
            return null;
        }

        $char = $this->source[$this->offset] ?? null;

        $converted = mb_convert_encoding($char, 'UTF-32', $this->encoding);
        $words = unpack('N*', $converted);
        return sprintf('%04X', reset($words));
    }

    /**
     * Rewinds by a single character.
     *
     * @return void
     */
    public function rewind()
    {
        if ($this->offset > 0) {
            $this->offset--;
        }
    }


    /**
     * Returns the currently consumed character.
     *
     * @return string|null
     */
    public function raw(): ?string
    {
        return $this->source[$this->offset] ?? null;
    }
}
