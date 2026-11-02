<?php
declare(strict_types=1);

abstract class Servico
{
    private string $descricao;
    private float $precoBase;

    public function __construct(string $descricao, float $precoBase)
    {
        $descricao = trim($descricao);
        if ($descricao === '') {
            throw new Exception('A descrição do serviço é obrigatória.');
        }
        if (!is_finite($precoBase) || $precoBase < 0) {
            throw new Exception('O preço base não pode ser negativo.');
        }

        $this->descricao = $descricao;
        $this->precoBase = $precoBase;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getPrecoBase(): float
    {
        return $this->precoBase;
    }

    abstract public function getTipo(): string;

    abstract public function calcularValor(): float;

    public function paraArray(): array
    {
        return [
            'tipo' => $this->getTipo(),
            'descricao' => $this->getDescricao(),
            'precoBase' => $this->getPrecoBase(),
            'valorFinal' => round($this->calcularValor(), 2),
        ];
    }

    public static function deArray(array $dados): Servico
    {
        $tipo = (string) ($dados['tipo'] ?? '');
        $descricao = (string) ($dados['descricao'] ?? '');
        $precoBase = (float) ($dados['precoBase'] ?? 0);

        return match ($tipo) {
            'revisao' => new Revisao($descricao, $precoBase, (string) ($dados['nivel'] ?? '')),
            'troca_oleo' => new TrocaOleo(
                $descricao,
                $precoBase,
                (float) ($dados['litros'] ?? 0),
                (float) ($dados['precoLitro'] ?? 0)
            ),
            'funilaria' => new Funilaria(
                $descricao,
                $precoBase,
                (int) ($dados['horas'] ?? 0),
                (float) ($dados['valorHora'] ?? 0)
            ),
            default => throw new Exception('Tipo de serviço desconhecido nos dados salvos.'),
        };
    }
}
