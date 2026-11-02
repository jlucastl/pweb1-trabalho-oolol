# Testes de orientação a objetos e camada web

## Ambiente e procedimento

Os testes abaixo foram executados no ambiente de preparação do projeto em **02/11/2026**, com **PHP 8.4.24** (CLI), servidor embutido e `curl`. O roteiro `php teste.php` usa um arquivo temporário para persistência e não altera `data/ordens.json`.

```bash
php -l public/index.php
php -l src/OrdemServico.php
php teste.php
```

A verificação de sintaxe retornou `No syntax errors detected` para todos os arquivos PHP do projeto. Saída real do teste automatizado:

```text
=== Testes da Oficina Mecânica ===
OK, aceito: Revisão completa = R$ 150,00
OK, aceito: Troca de óleo = R$ 160,00
OK, aceito: Funilaria = R$ 200,00
POLIMORFISMO: revisao -> R$ 150,00
POLIMORFISMO: troca_oleo -> R$ 160,00
POLIMORFISMO: funilaria -> R$ 200,00
OK, aceito: Composição: 3 serviços na mesma ordem
OK, aceito: Total polimórfico = R$ 510,00
OK, aceito: JSON: o array contém os 3 serviços
OK, recusado: Cliente com nome muito curto | O nome do cliente deve ter entre 3 e 100 caracteres.
OK, recusado: Placa fora do padrão brasileiro | A placa deve seguir o padrão brasileiro (ABC1234 ou ABC1D23).
OK, recusado: Litros de óleo iguais a zero | A troca de óleo deve usar mais de 0 e no máximo 15 litros.
OK, recusado: Preço base negativo | O preço base não pode ser negativo.
OK, recusado: Nível de revisão desconhecido | A revisão deve ser básica ou completa.
OK, recusado: Concluir uma ordem vazia | A ordem precisa ter pelo menos um serviço para ser concluída.
OK, aceito: Ordem pode ser concluída após incluir serviços
OK, recusado: Adicionar serviço após conclusão | Não é possível adicionar serviços a uma ordem concluída.
OK, recusado: Concluir a mesma ordem duas vezes | Esta ordem já foi concluída.
OK, aceito: Persistência JSON: ordem recuperada em nova leitura
OK, aceito: Identificador automático: próximo ID = 2
OK, aceito: JSON válido após salvar
=== Resultado: 18 testes OK; 0 falha(s) ===
```

## O que cada teste demonstra

| Teste | O que comprova |
|---|---|
| Revisão completa: R$ 150,00 | Sobrescrita de `calcularValor()` em `Revisao` (100 × 1,5) |
| Troca de óleo: R$ 160,00 | Fórmula `precoBase + litros × precoLitro` (40 + 4 × 30) |
| Funilaria: R$ 200,00 | Fórmula `precoBase + horas × valorHora` (50 + 2 × 75) |
| Três tipos no `foreach` | Polimorfismo com subclasses de `Servico` |
| Três itens e total R$ 510,00 | Composição e soma de serviços distintos |
| `paraArray()` com 3 serviços | Serialização para a rota JSON |
| Nome curto | Validação do cliente com `throw new Exception` |
| Placa inválida | Validação de veículo |
| Litros zero | Regra própria de `TrocaOleo` |
| Preço negativo | Regra comum da classe-mãe `Servico` |
| Nível inválido | Regra própria de `Revisao` |
| Conclusão vazia | Proteção do estado da ordem |
| Conclusão válida | Mudança autorizada de estado |
| Adicionar após concluir | Bloqueio de mudança em ordem concluída |
| Concluir duas vezes | Recusa de operação repetida |
| Persistência | Salvamento e leitura de objetos via JSON |
| Próximo ID | Cálculo sequencial baseado no maior identificador |
| JSON válido | Arquivo realmente contém JSON decodificável |

**Resultado:** 18 verificações aprovadas, nenhuma falha.

## Camada web: comandos executados

O servidor foi iniciado com a opção `-t public`, equivalente ao comando indicado no enunciado:

```bash
php -S localhost:8000 -t public -d display_errors=1 -d output_buffering=0
```

Durante a verificação automatizada, a porta local usada foi `18083`, mas os comandos abaixo estão escritos com a porta `8000` para você repetir.

### 1. Rota JSON (W2)

```bash
curl -i 'http://localhost:8000/?formato=json'
```

Cabeçalho observado:

```http
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
```

Resposta com base vazia, verificada antes da criação das amostras:

```json
{
    "sistema": "Oficina Mecânica",
    "quantidade": 0,
    "ordens": []
}
```

Depois de cadastrar uma troca de óleo pelo formulário, `/?formato=json&id=1` retornou, entre outros campos:

```json
{
    "id": 1,
    "status": "aberta",
    "servicos": [
        {
            "tipo": "troca_oleo",
            "descricao": "Troca de oleo",
            "precoBase": 40,
            "valorFinal": 180,
            "litros": 4,
            "precoLitro": 35
        }
    ],
    "total": 180
}
```

*Nota:* esse último bloco mostra apenas os campos relevantes; a resposta real inclui também o objeto `veiculo` com os dados do cliente.

### 2. Página inexistente (W1)

```bash
curl -i 'http://localhost:8000/?pagina=nao-existe'
curl -i 'http://localhost:8000/?pagina=detalhes&id=999'
```

Ambas as requisições receberam `HTTP/1.1 404 Not Found` com uma página HTML que utiliza o mesmo cabeçalho e menu das demais telas.

### 3. Formulário válido e redirecionamento (W3)

O envio por POST com nome `Ana Pereira`, placa `ABC1D23`, ano `2020`, serviço `troca_oleo`, preço base `40`, `4` litros e preço por litro `35` retornou:

```http
HTTP/1.1 303 See Other
Location: ?pagina=detalhes&id=1&sucesso=criada
```

A resposta JSON seguinte confirmou o total de **R$ 180,00**. O status 303 evita reenviar o formulário ao atualizar a página.

### 4. Formulário recusado (OO5 e W3)

Quando o nome enviado foi apenas `A`, a resposta HTML exibiu a exceção capturada pelo `catch`:

```html
<div class="alerta erro" role="alert">O nome do cliente deve ter entre 3 e 100 caracteres.</div>
```

Outros testes realizados: adicionar revisão completa no mesmo pedido fez o total passar de R$ 180,00 para **R$ 330,00**; concluir retornou HTTP 303; tentar adicionar serviço após concluir exibiu `Não é possível adicionar serviços a uma ordem concluída.`

### 5. Método proibido na rota JSON

```bash
curl -i -X POST 'http://localhost:8000/?formato=json'
```

Trechos da resposta real:

```http
HTTP/1.1 405 Method Not Allowed
Content-Type: application/json; charset=utf-8
Allow: GET
```

```json
{
    "erro": "A rota JSON aceita somente requisições GET."
}
```

### 6. Proteção de saída HTML (W4)

A função `h()` em `public/index.php` aplica `htmlspecialchars` com `ENT_QUOTES | ENT_SUBSTITUTE` a cada texto dinâmico exibido. Assim, por exemplo, um nome armazenado com caracteres de marcação como `<script>` é mostrado como texto escapado, em vez de virar um elemento HTML. A validação real de campos continua sendo feita no servidor, não no JavaScript da página.

## Como reproduzir os resultados

O arquivo que acompanha o ZIP contém **duas ordens fictícias iniciais** para facilitar a demonstração, portanto os IDs/quantidades podem ser diferentes dos exemplos de execução descritos acima. Para repetir exatamente o teste de criação da primeira ordem, salve uma cópia dos dados e redefina `data/ordens.json` para `[]` antes do POST. A execução de `php teste.php` não depende desse reset.
