<?php
declare(strict_types=1);

class Revisao extends Servico
{
    private string $nivel;

    public function __construct(string $descricao, float $precoBase, string $nivel)
    {
        parent::__construct($descricao, $precoBase);
        if (!in_array($nivel, ['basica', 'completa'], true)) {
            throw new Exception('A revisão deve ser básica ou completa.');
        }
        $this->nivel = $nivel;
    }

    public function getNivel(): string
    {
        return $this->nivel;
    }

    public function getTipo(): string
    {
        return 'revisao';
    }

    public function calcularValor(): float
    {
        return round($this->getPrecoBase() * ($this->nivel === 'completa' ? 1.5 : 1.0), 2);
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), ['nivel' => $this->nivel]);
    }
}
