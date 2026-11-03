<?php
declare(strict_types=1);

class RepositorioOrdens
{
    private string $arquivo;

    public function __construct(string $arquivo)
    {
        if (trim($arquivo) === '') {
            throw new Exception('O caminho do arquivo de dados é obrigatório.');
        }
        $this->arquivo = $arquivo;
    }

    /** @return OrdemServico[] */
    public function listar(): array
    {
        if (!file_exists($this->arquivo)) {
            return [];
        }
        $conteudo = file_get_contents($this->arquivo);
        if ($conteudo === false) {
            throw new Exception('Não foi possível ler o arquivo de dados.');
        }
        try {
            $dados = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $erro) {
            throw new Exception('O arquivo de dados contém JSON inválido.', 0, $erro);
        }
        if (!is_array($dados) || !array_is_list($dados)) {
            throw new Exception('O arquivo de dados deve conter uma lista de ordens.');
        }
        $ordens = [];
        foreach ($dados as $item) {
            $ordens[] = OrdemServico::deArray($item);
        }
        return $ordens;
    }

    public function buscar(int $id): ?OrdemServico
    {
        foreach ($this->listar() as $ordem) {
            if ($ordem->getId() === $id) {
                return $ordem;
            }
        }
        return null;
    }

    public function proximoId(): int
    {
        $maiorId = 0;
        foreach ($this->listar() as $ordem) {
            $maiorId = max($maiorId, $ordem->getId());
        }
        return $maiorId + 1;
    }

    public function salvar(OrdemServico $ordem): void
    {
        $ordens = $this->listar();
        $substituiu = false;
        foreach ($ordens as $indice => $existente) {
            if ($existente->getId() === $ordem->getId()) {
                $ordens[$indice] = $ordem;
                $substituiu = true;
                break;
            }
        }
        if (!$substituiu) {
            $ordens[] = $ordem;
        }
        $dados = [];
        foreach ($ordens as $item) {
            $dados[] = $item->paraArray();
        }
        try {
            $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $erro) {
            throw new Exception('Falha ao converter as ordens para JSON.', 0, $erro);
        }
        $pasta = dirname($this->arquivo);
        if (!is_dir($pasta) || file_put_contents($this->arquivo, $json . "\n", LOCK_EX) === false) {
            throw new Exception('Não foi possível salvar o arquivo de dados.');
        }
    }
}
