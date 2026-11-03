<?php
declare(strict_types=1);

class Veiculo
{
    private int $id;
    private Cliente $cliente;
    private string $placa;
    private string $modelo;
    private int $ano;

    public function __construct(int $id, Cliente $cliente, string $placa, string $modelo, int $ano)
    {
        $placa = strtoupper(trim($placa));
        $modelo = trim($modelo);
        if ($id < 1) {
            throw new Exception('O ID do veículo deve ser positivo.');
        }
        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
            throw new Exception('A placa deve seguir o padrão brasileiro (ABC1234 ou ABC1D23).');
        }
        if (strlen($modelo) < 2 || strlen($modelo) > 80) {
            throw new Exception('O modelo do veículo deve ter entre 2 e 80 caracteres.');
        }
        if ($ano < 1950 || $ano > (int) date('Y') + 1) {
            throw new Exception('O ano do veículo é inválido.');
        }
        $this->id = $id;
        $this->cliente = $cliente;
        $this->placa = $placa;
        $this->modelo = $modelo;
        $this->ano = $ano;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'cliente' => $this->cliente->paraArray(),
            'placa' => $this->placa,
            'modelo' => $this->modelo,
            'ano' => $this->ano,
        ];
    }

    public static function deArray(array $dados): Veiculo
    {
        return new Veiculo(
            (int) $dados['id'],
            Cliente::deArray($dados['cliente']),
            (string) $dados['placa'],
            (string) $dados['modelo'],
            (int) $dados['ano']
        );
    }
}
