<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/carregar.php';

function h(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function campo(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    if (!is_string($valor)) {
        throw new Exception('O campo ' . $nome . ' é inválido.');
    }
    return trim($valor);
}

function campoNumero(string $nome): float
{
    $valor = str_replace(',', '.', campo($nome));
    if ($valor === '' || !is_numeric($valor)) {
        throw new Exception('Informe um número válido no campo ' . $nome . '.');
    }
    $numero = (float) $valor;
    if (!is_finite($numero)) {
        throw new Exception('O número informado em ' . $nome . ' é inválido.');
    }
    return $numero;
}

function campoInteiro(string $nome): int
{
    $valor = filter_var(campo($nome), FILTER_VALIDATE_INT);
    if ($valor === false) {
        throw new Exception('Informe um número inteiro válido no campo ' . $nome . '.');
    }
    return $valor;
}

function valorAntigo(string $nome, string $padrao = ''): string
{
    $valor = $_POST[$nome] ?? $padrao;
    return is_string($valor) ? $valor : $padrao;
}

function construirServico(): Servico
{
    $tipo = campo('tipo_servico');
    $descricao = campo('descricao');
    $precoBase = campoNumero('preco_base');

    return match ($tipo) {
        'revisao' => new Revisao($descricao, $precoBase, campo('nivel')),
        'troca_oleo' => new TrocaOleo(
            $descricao,
            $precoBase,
            campoNumero('litros'),
            campoNumero('preco_litro')
        ),
        'funilaria' => new Funilaria(
            $descricao,
            $precoBase,
            campoInteiro('horas'),
            campoNumero('valor_hora')
        ),
        default => throw new Exception('Selecione um tipo de serviço válido.'),
    };
}

function dinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function nomeServico(Servico $servico): string
{
    return match ($servico->getTipo()) {
        'revisao' => 'Revisão',
        'troca_oleo' => 'Troca de óleo',
        'funilaria' => 'Funilaria',
        default => 'Serviço',
    };
}

$pagina = $_GET['pagina'] ?? 'inicio';
$pagina = is_string($pagina) ? $pagina : '';
$formato = $_GET['formato'] ?? '';
$formato = is_string($formato) ? $formato : '';
$paginasPermitidas = ['inicio', 'ordens', 'nova', 'detalhes', 'sobre'];
$erro = '';
$ordens = [];
$ordemAtual = null;
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$repositorio = new RepositorioOrdens(__DIR__ . '/../data/ordens.json');
$paginaInexistente = !in_array($pagina, $paginasPermitidas, true);

try {
    if ($paginaInexistente) {
        http_response_code(404);
    } else {
        $ordens = $repositorio->listar();
        if ($pagina === 'detalhes') {
            $ordemAtual = $id === false ? null : $repositorio->buscar($id);
            if ($ordemAtual === null) {
                http_response_code(404);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($formato === 'json') {
                throw new Exception('A rota JSON aceita somente requisições GET.');
            }

            $acao = campo('acao');
            if ($pagina === 'nova' && $acao === 'criar') {
                $novoId = $repositorio->proximoId();
                $cliente = new Cliente($novoId, campo('nome'), campo('telefone'));
                $veiculo = new Veiculo(
                    $novoId,
                    $cliente,
                    campo('placa'),
                    campo('modelo'),
                    campoInteiro('ano')
                );
                $novaOrdem = new OrdemServico($novoId, $veiculo);
                $novaOrdem->adicionarServico(construirServico());
                $repositorio->salvar($novaOrdem);
                header('Location: ?pagina=detalhes&id=' . $novoId . '&sucesso=criada', true, 303);
                exit;
            }
            if ($pagina === 'detalhes' && $ordemAtual !== null && $acao === 'adicionar') {
                $ordemAtual->adicionarServico(construirServico());
                $repositorio->salvar($ordemAtual);
                header('Location: ?pagina=detalhes&id=' . $ordemAtual->getId() . '&sucesso=adicionado', true, 303);
                exit;
            }
            if ($pagina === 'detalhes' && $ordemAtual !== null && $acao === 'concluir') {
                $ordemAtual->concluir();
                $repositorio->salvar($ordemAtual);
                header('Location: ?pagina=detalhes&id=' . $ordemAtual->getId() . '&sucesso=concluida', true, 303);
                exit;
            }
            throw new Exception('Ação não permitida nesta página.');
        }
    }
} catch (Exception $excecao) {
    $erro = $excecao->getMessage();
}

if ($formato === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        $resposta = ['erro' => $erro !== '' ? $erro : 'Método não permitido.'];
    } elseif ($paginaInexistente || ($pagina === 'detalhes' && $ordemAtual === null)) {
        http_response_code(404);
        $resposta = ['erro' => 'Página ou ordem não encontrada.'];
    } elseif ($erro !== '') {
        http_response_code(500);
        $resposta = ['erro' => $erro];
    } elseif ($id !== false) {
        $ordemJson = $repositorio->buscar($id);
        if ($ordemJson === null) {
            http_response_code(404);
            $resposta = ['erro' => 'Ordem de serviço não encontrada.'];
        } else {
            $resposta = $ordemJson->paraArray();
        }
    } else {
        $lista = [];
        foreach ($ordens as $ordem) {
            $lista[] = $ordem->paraArray();
        }
        $resposta = ['sistema' => 'Oficina Mecânica', 'quantidade' => count($lista), 'ordens' => $lista];
    }
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    exit;
}

$naoEncontrada = $paginaInexistente || ($pagina === 'detalhes' && $ordemAtual === null);
if ($naoEncontrada) {
    http_response_code(404);
} elseif ($erro !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(500);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $pagina !== 'nova' && $pagina !== 'detalhes') {
    http_response_code(405);
    header('Allow: GET');
}

$sucesso = $_GET['sucesso'] ?? '';
$sucesso = is_string($sucesso) ? $sucesso : '';
$mensagensSucesso = [
    'criada' => 'Ordem de serviço cadastrada com sucesso.',
    'adicionado' => 'Serviço adicionado à ordem com sucesso.',
    'concluida' => 'Ordem de serviço concluída com sucesso.',
];
$titulos = [
    'inicio' => 'Início',
    'ordens' => 'Ordens de serviço',
    'nova' => 'Nova ordem',
    'detalhes' => 'Detalhes da ordem',
    'sobre' => 'Sobre o sistema',
];
$tituloPagina = $naoEncontrada ? 'Página não encontrada' : ($titulos[$pagina] ?? 'Oficina Mecânica');
$abertas = 0;
foreach ($ordens as $itemOrdem) {
    if ($itemOrdem->getStatus() === 'aberta') {
        $abertas++;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($tituloPagina) ?> | Oficina Mecânica</title>
    <link rel="stylesheet" href="/estilo.css">
</head>
<body>
<header class="cabecalho">
    <div class="container barra">
        <a class="marca" href="?pagina=inicio"><span class="logo">⚙</span> Oficina Mecânica</a>
        <nav aria-label="Navegação principal">
            <a class="<?= $pagina === 'inicio' ? 'ativo' : '' ?>" href="?pagina=inicio">Início</a>
            <a class="<?= $pagina === 'ordens' ? 'ativo' : '' ?>" href="?pagina=ordens">Ordens</a>
            <a class="<?= $pagina === 'nova' ? 'ativo' : '' ?>" href="?pagina=nova">Nova ordem</a>
            <a class="<?= $pagina === 'sobre' ? 'ativo' : '' ?>" href="?pagina=sobre">Sobre</a>
        </nav>
    </div>
</header>
<main class="container conteudo">
    <?php if ($naoEncontrada): ?>
        <section class="cartao destaque">
            <div class="eyebrow">ERRO 404</div>
            <h1>Não encontramos o que você procura</h1>
            <p>A página ou a ordem de serviço informada não existe.</p>
            <a class="botao" href="?pagina=ordens">Ver ordens cadastradas</a>
        </section>
    <?php else: ?>
        <?php if ($erro !== ''): ?>
            <div class="alerta erro" role="alert"><?= h($erro) ?></div>
        <?php endif; ?>
        <?php if (isset($mensagensSucesso[$sucesso]) && $erro === ''): ?>
            <div class="alerta sucesso" role="status"><?= h($mensagensSucesso[$sucesso]) ?></div>
        <?php endif; ?>

        <?php if ($pagina === 'inicio'): ?>
            <section class="hero">
                <div>
                    <div class="eyebrow">CONTROLE DE OFICINA</div>
                    <h1>Organize os serviços de cada veículo.</h1>
                    <p>Cadastre clientes e veículos, calcule o orçamento e acompanhe suas ordens de serviço em um só lugar.</p>
                    <div class="acoes"><a class="botao" href="?pagina=nova">+ Criar ordem</a><a class="botao secundario" href="?pagina=ordens">Ver ordens</a></div>
                </div>
                <div class="painel-hero" aria-hidden="true"><span>⚙</span><strong>Serviço bem feito começa com organização.</strong></div>
            </section>
            <div class="estatisticas">
                <article class="cartao"><span>Ordens cadastradas</span><strong><?= h(count($ordens)) ?></strong></article>
                <article class="cartao"><span>Ordens em aberto</span><strong><?= h($abertas) ?></strong></article>
                <article class="cartao"><span>Ordens concluídas</span><strong><?= h(count($ordens) - $abertas) ?></strong></article>
            </div>
            <section class="cartao"><div class="linha-titulo"><h2>Últimas ordens</h2><a href="?pagina=ordens">Ver todas</a></div>
                <?php if ($ordens === []): ?><p>Nenhuma ordem cadastrada. Comece criando a primeira!</p>
                <?php else: ?><div class="lista-ordens">
                    <?php foreach (array_slice(array_reverse($ordens), 0, 4) as $itemOrdem): ?>
                        <a class="item-ordem" href="?pagina=detalhes&amp;id=<?= h($itemOrdem->getId()) ?>">
                            <span><strong>#<?= h($itemOrdem->getId()) ?> · <?= h($itemOrdem->getVeiculo()->getModelo()) ?></strong><small><?= h($itemOrdem->getVeiculo()->getCliente()->getNome()) ?> · <?= h($itemOrdem->getVeiculo()->getPlaca()) ?></small></span>
                            <span class="final-item"><b><?= h(dinheiro($itemOrdem->calcularTotal())) ?></b><span class="etiqueta <?= h($itemOrdem->getStatus()) ?>"><?= h($itemOrdem->getStatus()) ?></span></span>
                        </a>
                    <?php endforeach; ?>
                </div><?php endif; ?>
            </section>

        <?php elseif ($pagina === 'ordens'): ?>
            <div class="linha-titulo titulo-pagina"><div><div class="eyebrow">ACOMPANHAMENTO</div><h1>Ordens de serviço</h1><p>Consulte valores, veículos e andamento de cada ordem.</p></div><a class="botao" href="?pagina=nova">+ Nova ordem</a></div>
            <section class="cartao">
                <?php if ($ordens === []): ?><p>Não há ordens cadastradas.</p>
                <?php else: ?>
                    <div class="tabela-rolagem"><table><thead><tr><th>Ordem</th><th>Cliente</th><th>Veículo</th><th>Serviços</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
                    <?php foreach (array_reverse($ordens) as $itemOrdem): ?>
                        <tr>
                            <td><strong>#<?= h($itemOrdem->getId()) ?></strong></td>
                            <td><?= h($itemOrdem->getVeiculo()->getCliente()->getNome()) ?></td>
                            <td><?= h($itemOrdem->getVeiculo()->getModelo()) ?><small><?= h($itemOrdem->getVeiculo()->getPlaca()) ?></small></td>
                            <td><?= h(count($itemOrdem->getServicos())) ?></td>
                            <td><?= h(dinheiro($itemOrdem->calcularTotal())) ?></td>
                            <td><span class="etiqueta <?= h($itemOrdem->getStatus()) ?>"><?= h($itemOrdem->getStatus()) ?></span></td>
                            <td><a href="?pagina=detalhes&amp;id=<?= h($itemOrdem->getId()) ?>">Abrir</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>

        <?php elseif ($pagina === 'nova'): ?>
            <div class="titulo-pagina"><div class="eyebrow">NOVO ATENDIMENTO</div><h1>Criar ordem de serviço</h1><p>Informe os dados do cliente, veículo e primeiro serviço.</p></div>
            <form method="POST" action="?pagina=nova" class="formulario">
                <input type="hidden" name="acao" value="criar">
                <section class="cartao"><h2>1. Dados do cliente</h2><div class="campos duas-colunas">
                    <label>Nome completo <input name="nome" required minlength="3" maxlength="100" value="<?= h(valorAntigo('nome')) ?>" placeholder="Ex.: Maria da Silva"></label>
                    <label>Telefone <input name="telefone" required maxlength="20" value="<?= h(valorAntigo('telefone')) ?>" placeholder="(85) 99999-9999"></label>
                </div></section>
                <section class="cartao"><h2>2. Dados do veículo</h2><div class="campos tres-colunas">
                    <label>Placa <input name="placa" required maxlength="7" value="<?= h(valorAntigo('placa')) ?>" placeholder="ABC1D23"></label>
                    <label>Modelo <input name="modelo" required maxlength="80" value="<?= h(valorAntigo('modelo')) ?>" placeholder="Ex.: Gol 1.6"></label>
                    <label>Ano <input name="ano" type="number" required min="1950" max="<?= h((int) date('Y') + 1) ?>" value="<?= h(valorAntigo('ano', '2020')) ?>"></label>
                </div></section>
                <section class="cartao"><h2>3. Primeiro serviço</h2>
                    <div class="campos duas-colunas">
                        <label>Tipo de serviço <select name="tipo_servico" class="seletor-servico"><option value="revisao" <?= valorAntigo('tipo_servico', 'revisao') === 'revisao' ? 'selected' : '' ?>>Revisão</option><option value="troca_oleo" <?= valorAntigo('tipo_servico') === 'troca_oleo' ? 'selected' : '' ?>>Troca de óleo</option><option value="funilaria" <?= valorAntigo('tipo_servico') === 'funilaria' ? 'selected' : '' ?>>Funilaria</option></select></label>
                        <label>Preço base (R$) <input name="preco_base" type="number" min="0" step="0.01" required value="<?= h(valorAntigo('preco_base', '100')) ?>"></label>
                    </div>
                    <label>Descrição <input name="descricao" required maxlength="180" value="<?= h(valorAntigo('descricao')) ?>" placeholder="Ex.: Revisão preventiva de 10.000 km"></label>
                    <div class="opcoes-servico" data-tipo="revisao"><label>Nível da revisão <select name="nivel"><option value="basica" <?= valorAntigo('nivel', 'basica') === 'basica' ? 'selected' : '' ?>>Básica (preço base)</option><option value="completa" <?= valorAntigo('nivel') === 'completa' ? 'selected' : '' ?>>Completa (+50%)</option></select></label></div>
                    <div class="opcoes-servico duas-colunas campos" data-tipo="troca_oleo"><label>Litros de óleo <input name="litros" type="number" min="0.01" max="15" step="0.01" value="<?= h(valorAntigo('litros', '4')) ?>"></label><label>Preço por litro (R$) <input name="preco_litro" type="number" min="0.01" step="0.01" value="<?= h(valorAntigo('preco_litro', '35')) ?>"></label></div>
                    <div class="opcoes-servico duas-colunas campos" data-tipo="funilaria"><label>Horas de trabalho <input name="horas" type="number" min="1" max="200" value="<?= h(valorAntigo('horas', '2')) ?>"></label><label>Valor por hora (R$) <input name="valor_hora" type="number" min="0.01" step="0.01" value="<?= h(valorAntigo('valor_hora', '80')) ?>"></label></div>
                </section>
                <div class="acoes"><button class="botao" type="submit">Salvar ordem de serviço</button><a class="botao secundario" href="?pagina=ordens">Cancelar</a></div>
            </form>

        <?php elseif ($pagina === 'detalhes' && $ordemAtual !== null): ?>
            <div class="linha-titulo titulo-pagina"><div><div class="eyebrow">ORDEM DE SERVIÇO #<?= h($ordemAtual->getId()) ?></div><h1>Detalhes do atendimento</h1><p>Confira os serviços e o valor do orçamento.</p></div><span class="etiqueta <?= h($ordemAtual->getStatus()) ?>"><?= h($ordemAtual->getStatus()) ?></span></div>
            <div class="grid-detalhes">
                <section class="cartao"><h2>Cliente</h2><p><strong><?= h($ordemAtual->getVeiculo()->getCliente()->getNome()) ?></strong></p><p><?= h($ordemAtual->getVeiculo()->getCliente()->getTelefone()) ?></p></section>
                <section class="cartao"><h2>Veículo</h2><p><strong><?= h($ordemAtual->getVeiculo()->getModelo()) ?></strong></p><p><?= h($ordemAtual->getVeiculo()->getPlaca()) ?> · Ano <?= h($ordemAtual->getVeiculo()->getAno()) ?></p></section>
            </div>
            <section class="cartao"><div class="linha-titulo"><h2>Serviços realizados / previstos</h2><strong><?= h(count($ordemAtual->getServicos())) ?> item(ns)</strong></div>
                <div class="lista-ordens">
                <?php foreach ($ordemAtual->getServicos() as $servico): ?>
                    <div class="item-ordem"><span><strong><?= h(nomeServico($servico)) ?></strong><small><?= h($servico->getDescricao()) ?></small>
                        <?php if ($servico instanceof Revisao): ?><small>Nível: <?= h($servico->getNivel()) ?></small><?php endif; ?>
                        <?php if ($servico instanceof TrocaOleo): ?><small><?= h($servico->getLitros()) ?> litro(s) × <?= h(dinheiro($servico->getPrecoLitro())) ?></small><?php endif; ?>
                        <?php if ($servico instanceof Funilaria): ?><small><?= h($servico->getHoras()) ?> hora(s) × <?= h(dinheiro($servico->getValorHora())) ?></small><?php endif; ?>
                        </span><b><?= h(dinheiro($servico->calcularValor())) ?></b></div>
                <?php endforeach; ?>
                </div><div class="total"><span>Total da ordem</span><strong><?= h(dinheiro($ordemAtual->calcularTotal())) ?></strong></div>
            </section>
            <?php if ($ordemAtual->getStatus() === 'aberta'): ?>
            <section class="cartao"><h2>Adicionar outro serviço</h2><p>Vários serviços podem fazer parte da mesma ordem.</p>
                <form method="POST" action="?pagina=detalhes&amp;id=<?= h($ordemAtual->getId()) ?>" class="formulario-interno">
                    <input type="hidden" name="acao" value="adicionar">
                    <div class="campos duas-colunas"><label>Tipo <select name="tipo_servico" class="seletor-servico"><option value="revisao" <?= valorAntigo('tipo_servico', 'revisao') === 'revisao' ? 'selected' : '' ?>>Revisão</option><option value="troca_oleo" <?= valorAntigo('tipo_servico') === 'troca_oleo' ? 'selected' : '' ?>>Troca de óleo</option><option value="funilaria" <?= valorAntigo('tipo_servico') === 'funilaria' ? 'selected' : '' ?>>Funilaria</option></select></label>
                        <label>Preço base (R$) <input name="preco_base" type="number" min="0" step="0.01" required value="<?= h(valorAntigo('preco_base', '100')) ?>"></label></div>
                    <label>Descrição <input name="descricao" required value="<?= h(valorAntigo('descricao')) ?>" placeholder="Descreva o serviço"></label>
                    <div class="opcoes-servico" data-tipo="revisao"><label>Nível da revisão <select name="nivel"><option value="basica" <?= valorAntigo('nivel', 'basica') === 'basica' ? 'selected' : '' ?>>Básica</option><option value="completa" <?= valorAntigo('nivel') === 'completa' ? 'selected' : '' ?>>Completa (+50%)</option></select></label></div>
                    <div class="opcoes-servico campos duas-colunas" data-tipo="troca_oleo"><label>Litros de óleo <input name="litros" type="number" min="0.01" max="15" step="0.01" value="<?= h(valorAntigo('litros', '4')) ?>"></label><label>Preço por litro (R$) <input name="preco_litro" type="number" min="0.01" step="0.01" value="<?= h(valorAntigo('preco_litro', '35')) ?>"></label></div>
                    <div class="opcoes-servico campos duas-colunas" data-tipo="funilaria"><label>Horas <input name="horas" type="number" min="1" max="200" value="<?= h(valorAntigo('horas', '2')) ?>"></label><label>Valor por hora (R$) <input name="valor_hora" type="number" min="0.01" step="0.01" value="<?= h(valorAntigo('valor_hora', '80')) ?>"></label></div>
                    <button class="botao" type="submit">+ Adicionar serviço</button>
                </form>
            </section>
            <form method="POST" action="?pagina=detalhes&amp;id=<?= h($ordemAtual->getId()) ?>" class="concluir-form"><input type="hidden" name="acao" value="concluir"><button class="botao secundario" type="submit">Concluir esta ordem</button></form>
            <?php else: ?><div class="alerta sucesso">Esta ordem está concluída e não recebe novos serviços.</div><?php endif; ?>
            <p class="links-pequenos"><a href="?pagina=ordens">← Voltar à lista</a> · <a href="?formato=json&amp;id=<?= h($ordemAtual->getId()) ?>">Ver esta ordem em JSON</a></p>

        <?php elseif ($pagina === 'sobre'): ?>
            <div class="titulo-pagina"><div class="eyebrow">PROJETO ACADÊMICO</div><h1>Sobre o sistema</h1><p>Projeto de Programação Web I: Sistema Web Orientado a Objetos em PHP.</p></div>
            <section class="cartao"><h2>O que este sistema faz?</h2><p>Permite cadastrar uma ordem associada a um veículo e a um cliente, adicionar serviços de diferentes tipos, calcular o total e concluir o atendimento.</p><h2>Onde entram os conceitos de POO?</h2><p><strong>Herança:</strong> Revisao, TrocaOleo e Funilaria estendem Servico. <strong>Polimorfismo:</strong> cada serviço calcula seu valor de maneira diferente. <strong>Composição:</strong> OrdemServico guarda uma lista de objetos Servico. <strong>Encapsulamento:</strong> os atributos são privados e acessados por getters.</p><h2>Camada web</h2><p>As páginas usam <code>?pagina=...</code>, o formulário usa POST e a API JSON de leitura usa <a href="?formato=json"><code>?formato=json</code></a>. As ordens ficam no arquivo JSON fora da pasta pública.</p></section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<footer class="rodape"><div class="container"><span>Oficina Mecânica · Programação Web I</span><a href="?formato=json">Dados em JSON</a></div></footer>
<script>

for (const seletor of document.querySelectorAll('.seletor-servico')) {
    const formulario = seletor.closest('form');
    const atualizar = () => {
        for (const grupo of formulario.querySelectorAll('.opcoes-servico')) {
            const ativo = grupo.dataset.tipo === seletor.value;
            grupo.hidden = !ativo;
            for (const campo of grupo.querySelectorAll('input, select')) {
                campo.disabled = !ativo;
            }
        }
    };
    seletor.addEventListener('change', atualizar);
    atualizar();
}
</script>
</body>
</html>
