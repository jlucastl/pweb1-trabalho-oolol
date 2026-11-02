<?php
declare(strict_types=1);

require_once __DIR__ . '/src/carregar.php';

$repositorio = new RepositorioOrdens(__DIR__ . '/data/ordens.json');
if ($repositorio->listar() !== []) {
    echo "O arquivo já contém ordens. Nada foi sobrescrito.\n";
    exit;
}

$cliente1 = new Cliente(1, 'Mariana Lopes', '(85) 99999-1111');
$carro1 = new Veiculo(1, $cliente1, 'ABC1D23', 'Chevrolet Onix', 2021);
$ordem1 = new OrdemServico(1, $carro1);
$ordem1->adicionarServico(new Revisao('Revisão preventiva', 180.0, 'completa'));
$ordem1->adicionarServico(new TrocaOleo('Troca de óleo 5W-30', 45.0, 4.0, 40.0));
$repositorio->salvar($ordem1);

$cliente2 = new Cliente(2, 'Pedro Almeida', '(85) 98888-2222');
$carro2 = new Veiculo(2, $cliente2, 'HJK1234', 'Volkswagen Gol', 2018);
$ordem2 = new OrdemServico(2, $carro2);
$ordem2->adicionarServico(new Funilaria('Reparo do para-lama', 80.0, 3, 90.0));
$ordem2->concluir();
$repositorio->salvar($ordem2);

echo "Duas ordens fictícias foram criadas em data/ordens.json.\n";
