<?php
declare(strict_types=1);

class TrocaOleo extends Servico
{
    private float $litros;
    private float $precoLitro;

    public function __construct(string $descricao, float $precoBase, float $litros, float $precoLitro)
    {
        parent::__construct($descricao, $precoBase);
        if (!is_finite($litros) || $litros <= 0 || $litros > 15) {
            throw new Exception('A troca de óleo deve usar mais de 0 e no máximo 15 litros.');
        }
        if (!is_finite($precoLitro) || $precoLitro <= 0) {
            throw new Exception('O preço por litro deve ser maior que zero.');
        }
        $this->litros = $litros;
        $this->precoLitro = $precoLitro;
    }

    public function getLitros(): float
    {
        return $this->litros;
    }

    public function getPrecoLitro(): float
    {
        return $this->precoLitro;
    }

    public function getTipo(): string
    {
        return 'troca_oleo';
    }

    public function calcularValor(): float
    {
        return round($this->getPrecoBase() + $this->litros * $this->precoLitro, 2);
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), [
            'litros' => $this->litros,
            'precoLitro' => $this->precoLitro,
        ]);
    }
}
