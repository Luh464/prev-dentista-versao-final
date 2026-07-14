# Sistema de Gestão Odontológica (Prev Dentistas) - Versão 3.0

Este repositório contém o histórico de evolução, engenharia de software e a refatoração completa do sistema de gerenciamento para clínicas odontológicas. O projeto foi reestruturado para sanar falhas críticas de acoplamento do sistema legado, além de implementar um módulo financeiro e operacional flexível para atender a cenários reais de negócio.

---

## A Equipe

O desenvolvimento e a refatoração foram realizados de forma colaborativa pelos acadêmicos:

* 👩‍💻 **Luciele Barra Sanches** — [GitHub](https://github.com/Luh464)
* 👨‍💻 **David Feitosa** — [GitHub](https://github.com/davidfeitosa22)
* 👨‍💻 **Welbert Leite** — [GitHub](https://github.com/welbertsantos-ops)
* 👩‍💻 **Laiz Rodrigues** — [GitHub](https://github.com/laizrodriguess)
* 👨‍💻 **Ricardo Duarte** — [GitHub](https://github.com/rickDG2004)

---

## Pilares Cruciais de Engenharia de Software e Negócio

O novo projeto foi desenvolvido sob a obrigatoriedade de tratar três grandes eixos fundamentais:

### 1. Arquitetura e Qualidade de Código (MVC)

* **Migração Estrutural:** Foi realizada a migração completa do código legado (estruturado, rígido e com alto acoplamento na raiz) para uma apresentação moderna baseada em **Orientação a Objetos (POO) em PHP**.
* **Divisão de Responsabilidades (MVC):** Aplicação estrita do padrão arquitetural para isolamento de conceitos:
  * **`src/Model/`:** Manipulação de dados, entidades e mapeamentos.
  * **`src/View/`:** Interfaces de usuário e componentes visuais totalmente isolados.
  * **`src/Controller/`:** Processamento das requisições e execução das regras de negócio.
* **Segurança e Infraestrutura:** Centralização de rotinas críticas dentro do diretório `config/` (`database.php`, `seguranca.php`, `session.php`, `controle_acesso.php`).

### 2. Painel Administrativo de Parâmetros Flexíveis (CRUD de Regras)

Para eliminar os parâmetros rígidos que antes estavam fixados diretamente no código-fonte (*hardcoded*), foi desenvolvido um painel administrativo que permite ao proprietário configurar e gerenciar as diretrizes de negócio via interface:

* **Módulo de Maquininhas de Cartão:**
  * Tela dedicada para o gerenciamento de taxas transacionais.
  * Configuração flexível de taxas de juros parametrizadas por **bandeira de cartão** (Visa, MasterCard, Elo, etc.) e por **número de parcelas** (de 1x a 10x).
  * Mecanismo de estorno/correção que permite alterar e recalcular valores retroativamente em caso de erros humanos no lançamento.
* **Módulo de Comissões dos Dentistas:** Substituição da antiga regra progressiva fixa (20% até R$ 10 mil e 30% acima disso) por um sistema dinâmico parametrizável com dois cenários de tomada de decisão para o administrador:
  * *Cenário 1 (Geral):* A regra de taxas e metas se aplica de forma unificada para todos os profissionais da clínica geral.
  * *Cenário 2 (Individual):* A regra se aplica individualmente, onde cada funcionário possui sua própria meta a ser batida.
  * *Casos Especializados:* Ajuste customizado para profissionais especializados que possuem taxas distintas (ex: endodontia/canal fixado em 50%), operando em conformidade tanto com o Cenário 1 quanto com o Cenário 2.
  * *Blindagem Histórica:** Qualquer alteração nos percentuais ou tetos financeiros **não incide em procedimentos já efetuados**, aplicando-se estritamente a novos lançamentos.
* **Módulo de Histórico e Atualização de Preços (`historico_precos`):** Quando o preço de um procedimento é atualizado na clínica, a nova tabela impacta apenas os futuros atendimentos. Dados históricos e relatórios passados permanecem intactos e imutáveis para fins de auditoria.

### 3. Novo Modelo de Rateio e Separação de Pagamentos (Casos Especializados)

Correção de uma inconsistência crítica do sistema antigo que somava indevidamente todo o valor do atendimento para o dentista executor, ignorando a divisão de trabalho e a captação do cliente.

* **Tratamento de Rateio Complexo:** Divisão automatizada de receitas em tratamentos especializados (como canais ou cirurgias complexas), fracionando o valor bruto em:
  * **50%** para o profissional especialista que executou fisicamente o procedimento.
  * **10%** para o clínico geral que realizou a avaliação inicial, diagnosticou e vendeu o tratamento ao paciente (exceto em casos de ortodontia).
  * **40%** restantes retidos para a clínica (deduzindo-se previamente as taxas de juros da maquininha de cartão).
* **Interface de Lançamento Dinâmica:** No momento do registro do atendimento, a tela renderiza campos dinâmicos que consultam o banco de dados em tempo real para vincular e identificar explicitamente: o **Dentista Captador/Avaliador** (10%) e o **Dentista Especialista Executor** (50%).
* **Transparência em Relatórios:** A tela final do relatório financeiro discrimina com precisão e de forma totalmente separada os valores líquidos pagos a cada um dos envolvidos, corrigindo a soma incorreta do sistema anterior.

---

## Modelo de Dados e Banco de Dados

Para dar suporte às novas regras de rateio e flexibilização de parâmetros, a base de dados evoluiu de **7 tabelas** para **12 tabelas** integradas na base atual (`clinica_prev_dentistas`).

### Estrutura das Tabelas Atuais

| Categoria                    | Tabela                        | Descrição                                                                                                     |
| :--------------------------- | :---------------------------- | :-------------------------------------------------------------------------------------------------------------- |
| **Cadastros Base**     | `usuarios`                  | Credenciais, níveis de acesso e identificação dos profissionais.                                             |
|                              | `pacientes`                 | Registro cadastral e dados clínicos dos pacientes.                                                             |
|                              | `procedimentos`             | Catálogo de procedimentos com seus respectivos valores base atuais.                                            |
| **Operacional**        | `atendimentos`              | Registro centralizador do evento da consulta odontológica.                                                     |
|                              | `atendimento_procedimentos` | Tabela associativa (N:N) que discrimina quais procedimentos compõem o atendimento.                             |
| **Financeiro e Fluxo** | `atendimento_pagamentos`    | Controle de entradas, parcelas e formas de pagamento utilizadas.                                                |
|                              | `despesas`                  | Controle do fluxo de saídas e custos operacionais fixos/variáveis.                                            |
| **Regras Dinâmicas**  | `taxa_cartao`               | Cadastro matriz de taxas de juros parametrizadas por bandeira e por parcelas (1x a 10x).                        |
| *(CRUD de Regras)*         | `regras_rateio`             | Parametrização dos tetos, cenários (Geral vs Individual) e metas de repasse.                                 |
|                              | `comissao`                  | Lançamentos individuais de créditos gerados para as carteiras dos dentistas.                                  |
| **Auditoria e Rateio** | `historico_rateio`          | Registro imutável contendo a separação explícita e auditada (10% captador, 50% especialista, 40% clínica). |
|                              | `historico_precos`          | Log temporal de preços de procedimentos para preservar integridade de relatórios retroativos.---              |

## Como Executar o Projeto

1. Certifique-se de possuir um servidor local configurado (como Apache e MySQL/MariaDB via XAMPP).
2. Clone o repositório para o diretório de publicação do seu servidor (`htdocs`).
3. Importação do Banco de Dados:
   * Crie um banco de dados vazio chamado `clinica_prev_dentistas`.
   * Importe e execute o arquivo estrutural `database.sql`.
   * Execute o script de automações contido em `database/corrigir_triggers.sql`.
4. Ajuste as credenciais de acesso local dentro de `config/database.php`.
5. Abra o navegador e acesse: `http://localhost/nome-do-seu-repositorio/public/index.php`.
## ---

🚀 **Versão Final (Repositório Atualizado):** Sistema com as regras mais recentes de 
flexibilização de taxa de cartão e atendimento sem indicação.

🔗 [Acesse aqui o Repositório da Versão Final](https://github.com/Luh464/prev-dentista-versao-4-atualizado)

