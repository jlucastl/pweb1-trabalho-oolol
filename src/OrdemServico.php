<?php
declare(strict_types=1);

class OrdemServico
{
    private int $id;
    private Veiculo $veiculo;
    /** @var Servico[] */
    private array $servicos;
    private string $status;

    public function __construct(int $id, Veiculo $veiculo)
    {
        if ($id < 1) {
            throw new Exception('O número da ordem deve ser positivo.');
        }
        $this->id = $id;
        $this->veiculo = $veiculo;
        $this->servicos = [];
        $this->status = 'aberta';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVeiculo(): Veiculo
    {
        return $this->veiculo;
    }

    /** @return Servico[] */
    public function getServicos(): array
    {
        return $this->servicos;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function adicionarServico(Servico $servico): void
    {
        if ($this->status === 'concluida') {
            throw new Exception('Não é possível adicionar serviços a uma ordem concluída.');
        }
        $this->servicos[] = $servico;
    }

    public function calcularTotal(): float
    {
        $total = 0.0;
        // Polimorfismo (OO3): objetos de classes diferentes respondem a calcularValor().
        foreach ($this->servicos as $servico) {
            $total += $servico->calcularValor();
        }
        return round($total, 2);
    }

    public function concluir(): void
    {
        if (count($this->servicos) === 0) {
            throw new Exception('A ordem precisa ter pelo menos um serviço para ser concluída.');
        }
        if ($this->status === 'concluida') {
            throw new Exception('Esta ordem já foi concluída.');
        }
        $this->status = 'concluida';
    }

    public function paraArray(): array
    {
        $lista = [];
        foreach ($this->servicos as $servico) {
            $lista[] = $servico->paraArray();
        }
        return [
            'id' => $this->id,
            'veiculo' => $this->veiculo->paraArray(),
            'status' => $this->status,
            'servicos' => $lista,
            'total' => $this->calcularTotal(),
        ];
    }

    public static function deArray(array $dados): OrdemServico
    {
        $ordem = new OrdemServico((int) $dados['id'], Veiculo::deArray($dados['veiculo']));
        foreach ($dados['servicos'] as $dadosServico) {
            $ordem->adicionarServico(Servico::deArray($dadosServico));
        }
        if (($dados['status'] ?? 'aberta') === 'concluida') {
            $ordem->concluir();
        } elseif (($dados['status'] ?? 'aberta') !== 'aberta') {
            throw new Exception('Status inválido nos dados salvos.');
        }
        return $ordem;
    }
}
