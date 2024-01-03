# Plano de Teste - Plugin AB Test X

**Versão:** 1.0

## Histórico das Alterações

| Data       | Versão | Descrição         | Autor     |
|------------|--------|-------------------|-----------|
| 03/01/2025 | 1.0    | Lançamento inicial| Everbero  |

## 1 - Introdução

Este plano de testes descreve as atividades, recursos e estratégias de teste para o plugin AB Test X. O objetivo é garantir que o plugin atenda aos requisitos funcionais e não funcionais especificados.

### Necessidade de Refatoração do Código

O desenvolvimento do plugin ocorreu sem uma estrutura de testes integrada. Isso levanta preocupações substanciais quanto à robustez e à estabilidade do código. A ausência de testes unitários ou de integração dificulta a garantia da qualidade do software, aumentando o risco de falhas, comportamentos inesperados e dificuldades na manutenção.

A refatoração é crucial para remediar essa lacuna. Ao incorporar testes unitários com o PHPUnit, buscamos atingir vários objetivos:

- **Garantia da Qualidade:** Testes unitários permitem identificar e corrigir erros no código, assegurando que cada unidade funcione conforme o esperado.

- **Manutenibilidade Aprimorada:** A introdução de testes facilita a identificação de problemas durante as fases iniciais do desenvolvimento, reduzindo a complexidade e os riscos associados a alterações futuras.

- **Redução de Bugs e Comportamentos Inesperados:** Os testes unitários ajudam a identificar potenciais problemas e comportamentos inadequados do software, mitigando a ocorrência de bugs em produção.

- **Documentação Dinâmica:** Ao escrever testes unitários, documentamos o comportamento esperado de cada parte do código, tornando mais fácil entender sua finalidade e uso.

A refatoração do código para incorporar testes unitários não apenas eleva a qualidade do software, mas também estabelece uma base sólida para desenvolvimentos futuros, promovendo um ciclo de desenvolvimento mais seguro e eficiente.

## 2 - Requisitos a Testar

### Requisitos Funcionais

1. Criação de Campanhas A/B
2. Distribuição Aleatória
3. Acompanhamento de Conversões
4. Armazenamento de Parâmetros de URL
5. Gerenciamento de Variantes
6. Interface Intuitiva

### Requisitos Não Funcionais

1. Desempenho
2. Segurança
3. Compatibilidade
4. Escalabilidade
5. Documentação

## 3 - Tipos de Teste

### Teste de Interface de Usuário

- **Objetivo:** Verificar a usabilidade e facilidade de uso da interface do plugin.
- **Técnica:** Manual
- **Estágio do Teste:** Aceitação
- **Abordagem do Teste:** Caixa Preta

### Teste de Desempenho

- **Objetivo:** Avaliar a velocidade e eficiência do plugin em lidar com diferentes cargas.
- **Técnica:** Automática
- **Estágio do Teste:** Sistema
- **Abordagem do Teste:** Caixa Branca

### Teste de Segurança

- **Objetivo:** Garantir a segurança dos dados do usuário.
- **Técnica:** Manual
- **Estágio do Teste:** Sistema
- **Abordagem do Teste:** Caixa Preta

### Teste de Compatibilidade

- **Objetivo:** Verificar a compatibilidade do plugin com diferentes ambientes.
- **Técnica:** Manual
- **Estágio do Teste:** Aceitação
- **Abordagem do Teste:** Caixa Preta

### Teste de Documentação

- **Objetivo:** Avaliar a clareza e abrangência da documentação fornecida.
- **Técnica:** Manual
- **Estágio do Teste:** Aceitação
- **Abordagem do Teste:** Caixa Preta

### Testes Unitários com PHPUnit

- **Objetivo:** Realizar testes unitários para garantir a correta funcionalidade das funções do plugin.
- **Técnica:** Utilização do framework PHPUnit para escrever e executar testes automatizados.
- **Estágio do Teste:** Fase de desenvolvimento do plugin, após a implementação de cada função.
- **Abordagem do Teste:** Testes de unidade para verificar o comportamento e a precisão das funções individuais.

## 4 - Recursos

### Ambiente de Teste - Software e Hardware

- **Hardware:** Computador com sistema operacional Windows, MacOS e Linux
- **Software:** Diferentes versões do WordPress, navegadores como Chrome, Firefox, Safari, Edge

### Ferramenta de Teste

- Selenium para testes de interface;
- JMeter para testes de desempenho;
- PHP Unit para testes unitários

## 5 - Cronograma

| Tipo de Teste        | Duração | Início       | Término      |
|----------------------|---------|--------------|--------------|
| Interface de Usuário | 2 dias  | 02/03/2024   | 04/03/2024   |
| Desempenho           | 5 dias  | 06/03/2024   | 10/03/2024   |
| Segurança            | 3 dias  | 13/03/2024   | 15/03/2024   |
| Compatibilidade      | 4 dias  | 18/03/2024   | 21/03/2024   |
| Documentação         | 2 dias  | 24/03/2024   | 25/03/2024   |
| Testes Unitários     | 7 dias  | 27/03/2024   | 03/04/2024   |