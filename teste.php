<?php
declare(strict_types=1);

require_once __DIR__ . '/src/carregar.php';

$passou = 0;
$falhou = 0;

function verificar(string $descricao, bool $condicao): void
{
    global $passou, $falhou;
    if ($condicao) {
        $passou++;
        echo "OK, aceito: {$descricao}\n";
    } else {
        $falhou++;
        echo "FALHOU: {$descricao}\n";
    }
}

function recusar(string $descricao, callable $acao): void
{
    global $passou, $falhou;
    try {
        $acao();
        $falhou++;
        echo "FALHOU, deveria recusar: {$descricao}\n";
    } catch (Exception $erro) {
        $passou++;
        echo "OK, recusado: {$descricao} | {$erro->getMessage()}\n";
    }
}

echo "=== Testes da Oficina Mecânica ===\n";

$cliente = new Cliente(1, 'Maria da Silva', '(85) 99999-1234');
$veiculo = new Veiculo(1, $cliente, 'ABC1D23', 'Gol 1.6', 2022);
$ordem = new OrdemServico(1, $veiculo);

$revisao = new Revisao('Revisão completa', 100.0, 'completa');
$oleo = new TrocaOleo('Óleo e filtro', 40.0, 4.0, 30.0);
$funilaria = new Funilaria('Pintura do para-choque', 50.0, 2, 75.0);

verificar('Revisão completa = R$ 150,00', $revisao->calcularValor() === 150.0);
verificar('Troca de óleo = R$ 160,00', $oleo->calcularValor() === 160.0);
verificar('Funilaria = R$ 200,00', $funilaria->calcularValor() === 200.0);

$ordem->adicionarServico($revisao);
$ordem->adicionarServico($oleo);
$ordem->adicionarServico($funilaria);

foreach ($ordem->getServicos() as $servico) {
    echo 'POLIMORFISMO: ' . $servico->getTipo() . ' -> R$ ' . number_format($servico->calcularValor(), 2, ',', '.') . "\n";
}
verificar('Composição: 3 serviços na mesma ordem', count($ordem->getServicos()) === 3);
verificar('Total polimórfico = R$ 510,00', $ordem->calcularTotal() === 510.0);
verificar('JSON: o array contém os 3 serviços', count($ordem->paraArray()['servicos']) === 3);

recusar('Cliente com nome muito curto', fn() => new Cliente(2, 'A', '85999991234'));
recusar('Placa fora do padrão brasileiro', fn() => new Veiculo(2, $cliente, 'INVALIDA', 'Gol', 2022));
recusar('Litros de óleo iguais a zero', fn() => new TrocaOleo('Troca', 50.0, 0.0, 35.0));
recusar('Preço base negativo', fn() => new Revisao('Teste', -10.0, 'basica'));
recusar('Nível de revisão desconhecido', fn() => new Revisao('Teste', 10.0, 'urgente'));
recusar('Concluir uma ordem vazia', function () use ($veiculo): void {
    (new OrdemServico(99, $veiculo))->concluir();
});

$ordem->concluir();
verificar('Ordem pode ser concluída após incluir serviços', $ordem->getStatus() === 'concluida');
recusar('Adicionar serviço após conclusão', fn() => $ordem->adicionarServico($oleo));
recusar('Concluir a mesma ordem duas vezes', fn() => $ordem->concluir());

$arquivoTemp = tempnam(sys_get_temp_dir(), 'oficina_');
if ($arquivoTemp === false) {
    throw new RuntimeException('Não foi possível preparar o arquivo temporário.');
}
try {
    file_put_contents($arquivoTemp, "[]\n");
    $repositorio = new RepositorioOrdens($arquivoTemp);
    $repositorio->salvar($ordem);
    $repositorioReaberto = new RepositorioOrdens($arquivoTemp);
    $recuperada = $repositorioReaberto->buscar(1);
    verificar('Persistência JSON: ordem recuperada em nova leitura',
        $recuperada !== null && $recuperada->calcularTotal() === 510.0 && $recuperada->getStatus() === 'concluida');
    verificar('Identificador automático: próximo ID = 2', $repositorioReaberto->proximoId() === 2);
    verificar('JSON válido após salvar', is_array(json_decode((string) file_get_contents($arquivoTemp), true)));
} finally {
    unlink($arquivoTemp);
}

echo "=== Resultado: {$passou} testes OK; {$falhou} falha(s) ===\n";
exit($falhou === 0 ? 0 : 1);
