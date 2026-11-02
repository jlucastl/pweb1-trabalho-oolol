<?php
declare(strict_types=1);

class Funilaria extends Servico
{
    private int $horas;
    private float $valorHora;

    public function __construct(string $descricao, float $precoBase, int $horas, float $valorHora)
    {
        parent::__construct($descricao, $precoBase);
        if ($horas < 1 || $horas > 200) {
            throw new Exception('A funilaria deve ter entre 1 e 200 horas.');
        }
        if (!is_finite($valorHora) || $valorHora <= 0) {
            throw new Exception('O valor da hora deve ser maior que zero.');
        }
        $this->horas = $horas;
        $this->valorHora = $valorHora;
    }

    public function getHoras(): int
    {
        return $this->horas;
    }

    public function getValorHora(): float
    {
        return $this->valorHora;
    }

    public function getTipo(): string
    {
        return 'funilaria';
    }

    public function calcularValor(): float
    {
        return round($this->getPrecoBase() + $this->horas * $this->valorHora, 2);
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), [
            'horas' => $this->horas,
            'valorHora' => $this->valorHora,
        ]);
    }
}
