<?php

declare(strict_types=1);

namespace ImranDev\UnicodePdf\Native;

class BinaryReader
{
    public int $offset = 0;

    public function __construct(
        protected string $data
    ) {}

    public function length(): int
    {
        return strlen($this->data);
    }

    public function seek(int $offset): void
    {
        $this->offset = $offset;
    }

    public function skip(int $bytes): void
    {
        $this->offset += $bytes;
    }

    public function remaining(): int
    {
        return strlen($this->data) - $this->offset;
    }

    public function bytes(int $length): string
    {
        $chunk = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $chunk;
    }

    public function u8(): int
    {
        return ord($this->bytes(1));
    }

    public function u16(): int
    {
        $unpacked = unpack('n', $this->bytes(2));

        return $unpacked[1] ?? 0;
    }

    public function i16(): int
    {
        $value = $this->u16();

        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    public function u32(): int
    {
        $unpacked = unpack('N', $this->bytes(4));

        return $unpacked[1] ?? 0;
    }

    public function tag(): string
    {
        return $this->bytes(4);
    }

    public function peekU16(): int
    {
        $unpacked = unpack('n', substr($this->data, $this->offset, 2));

        return $unpacked[1] ?? 0;
    }
}
