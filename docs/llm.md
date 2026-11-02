# Diário de utilização de LLM

# Ferramenta utilizada
ChatGPT, da OpenAI.

## Interação 1 — Planejamento e modelagem

**Período:** outubro de 2026.

**Solicitação:** auxílio na definição e organização das classes de um sistema de gerenciamento de oficina mecânica, considerando os conceitos de herança, composição, encapsulamento e polimorfismo.

**Resposta:** foram apresentadas sugestões para estruturar as classes `Servico`, `Revisao`, `TrocaOleo`, `Funilaria`, `Cliente`, `Veiculo` e `OrdemServico`, estabelecendo as relações entre elas.

**Decisão:** a modelagem proposta foi aproveitada por representar adequadamente os elementos de uma oficina mecânica e possibilitar a aplicação dos princípios de programação orientada a objetos exigidos na atividade.

## Interação 2 — Desenvolvimento das classes

**Período:** outubro de 2026.

**Solicitação:** orientações para desenvolver as classes em PHP, utilizando propriedades privadas, construtores, métodos, relacionamentos de herança e validações relacionadas às regras de funcionamento da oficina.

**Resposta:** foram fornecidas recomendações sobre a distribuição das classes em arquivos individuais, a construção dos métodos e a implementação das regras de negócio.

**Decisão:** as orientações foram utilizadas na organização do código, mantendo uma classe por arquivo. O funcionamento dos métodos e das validações foi verificado por meio do arquivo `teste.php`.

## Interação 3 — Armazenamento e persistência dos dados

**Período:** outubro e novembro de 2026.

**Solicitação:** orientações para implementar um mecanismo de armazenamento que permitisse conservar os registros de clientes, veículos e ordens de serviço mesmo após atualizar a página ou reiniciar o servidor.

**Resposta:** foi apresentada uma alternativa de persistência utilizando as funções `file_get_contents()`, `json_decode()`, `json_encode()` e `file_put_contents()` para realizar a leitura e a gravação de informações em arquivos JSON.

**Decisão:** optou-se pelo armazenamento em JSON, pois essa solução atende às necessidades do projeto sem exigir a configuração de um banco de dados, mantendo a implementação mais simples.

## Interação 4 — Expansão das funcionalidades

**Data:** 2 de novembro de 2026.

**Solicitação:** auxílio para ampliar as funcionalidades do sistema, permitindo o cadastro de clientes e veículos, a criação de ordens de serviço e a associação de diferentes serviços aos atendimentos, preservando o padrão visual da aplicação.

**Resposta:** foram propostas adaptações nos formulários de cadastro, no relacionamento entre clientes, veículos e ordens de serviço, além de ajustes necessários para manter os registros armazenados corretamente.

**Decisão:** as sugestões foram aproveitadas na organização das funcionalidades do arquivo `public/index.php`, mantendo a estrutura visual existente e realizando ajustes pontuais no CSS quando necessário.

## Interação 5 — Identificação e correção de erro

**Data:** 2 de novembro de 2026.

**Problema:** durante a verificação da sintaxe do arquivo principal com o comando `php -l public/index.php`, foi identificada a seguinte mensagem:

```text
PHP Fatal error: strict_types declaration must be the very first statement in the script
```

**Resposta:** a ferramenta esclareceu que a declaração `declare(strict_types=1)` deve aparecer no início do arquivo PHP, antes de outras instruções executáveis, respeitando a abertura `<?php`.

**Decisão:** a posição da declaração foi ajustada para atender às exigências da linguagem. Após a correção, a análise sintática pôde ser executada sem apresentar o erro anterior.

## Interação 6 — Organização da documentação

**Data:** 2 de novembro de 2026.

**Solicitação:** auxílio na revisão e atualização dos documentos do projeto, incluindo o `README.md`, a descrição da modelagem, os registros de testes e o diário de utilização de ferramentas de inteligência artificial, considerando as funcionalidades desenvolvidas para a oficina mecânica.

**Resposta:** foram apresentadas sugestões de reformulação dos textos, organização das informações técnicas e atualização das descrições para refletir a estrutura das classes, as regras de negócio e os recursos disponíveis no sistema.

**Decisão:** as sugestões foram utilizadas como referência para preparar a documentação, que posteriormente seria revisada e incorporada ao repositório do projeto.