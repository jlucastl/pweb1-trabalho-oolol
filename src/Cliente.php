<?php
declare(strict_types=1);

class Cliente
{
    private int $id;
    private string $nome;
    private string $telefone;

    public function __construct(int $id, string $nome, string $telefone)
    {
        $nome = trim($nome);
        $telefone = trim($telefone);
        if ($id < 1) {
            throw new Exception('O ID do cliente deve ser positivo.');
        }
        if (strlen($nome) < 3 || strlen($nome) > 100) {
            throw new Exception('O nome do cliente deve ter entre 3 e 100 caracteres.');
        }
        if (!preg_match('/^[0-9()+\-\s]{8,20}$/', $telefone)) {
            throw new Exception('Informe um telefone válido, com 8 a 20 caracteres.');
        }
        $this->id = $id;
        $this->nome = $nome;
        $this->telefone = $telefone;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getTelefone(): string
    {
        return $this->telefone;
    }

    public function paraArray(): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'telefone' => $this->telefone];
    }

    public static function deArray(array $dados): Cliente
    {
        return new Cliente((int) $dados['id'], (string) $dados['nome'], (string) $dados['telefone']);
    }
}
