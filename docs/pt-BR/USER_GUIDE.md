# FlowBPMN - Guia do Usuário

[![GLPI Version](https://img.shields.io/badge/GLPI-11.0+-orange.svg)](https://glpi-project.org/)
[![Languages](https://img.shields.io/badge/Languages-PT%20|%20EN%20|%20ES-green.svg)](https://github.com/diegojucah/FlowBPMN)

Manual completo para usuários do plugin FlowBPMN no GLPI.

---

## 📚 Índice

1. [Introdução](#introdução)
2. [O que é BPMN?](#o-que-é-bpmn)
3. [Primeiros Passos](#primeiros-passos)
4. [Interface do Editor](#interface-do-editor)
5. [Criando Diagramas](#criando-diagramas)
6. [Gerenciando Versões](#gerenciando-versões)
7. [Trabalhando com Templates](#trabalhando-com-templates)
8. [Importando Diagramas](#importando-diagramas)
9. [Exportando Diagramas](#exportando-diagramas)
10. [Boas Práticas](#boas-práticas)
11. [Perguntas Frequentes](#perguntas-frequentes)

---

## Introdução

O **FlowBPMN** é um plugin para GLPI que permite criar, editar e gerenciar diagramas BPMN (Business Process Model and Notation) diretamente em Tickets, Problemas e Mudanças.

### Para que serve?

- 📊 **Documentar processos** de atendimento
- 🔍 **Visualizar fluxos** de trabalho
- 📝 **Padronizar** procedimentos
- 🎓 **Treinar** equipes
- 🔄 **Melhorar** processos continuamente

### Quem pode usar?

- **Técnicos**: Documentar atendimentos
- **Analistas**: Mapear problemas complexos
- **Gerentes**: Planejar mudanças
- **Coordenadores**: Criar biblioteca de processos

---

## O que é BPMN?

**BPMN** (Business Process Model and Notation) é uma notação gráfica padronizada para modelar processos de negócio.

### Elementos Básicos

#### 1. Eventos (Círculos)

| Elemento | Nome | Uso |
|----------|------|-----|
| ⭕ | **Start Event** | Início do processo |
| ⭕ | **End Event** | Fim do processo |
| ⭕ | **Intermediate Event** | Evento durante o processo |

#### 2. Atividades (Retângulos)

| Elemento | Nome | Uso |
|----------|------|-----|
| ▭ | **Task** | Tarefa simples |
| ▭ | **Sub-Process** | Processo dentro de processo |
| ▭ | **Call Activity** | Chamada a outro processo |

#### 3. Gateways (Losangos)

| Elemento | Nome | Uso |
|----------|------|-----|
| ◇ | **Exclusive Gateway** | Decisão (OU) |
| ◇ | **Parallel Gateway** | Paralelo (E) |
| ◇ | **Inclusive Gateway** | Inclusivo |

#### 4. Fluxos (Setas)

| Elemento | Nome | Uso |
|----------|------|-----|
| → | **Sequence Flow** | Fluxo sequencial |
| ⇢ | **Message Flow** | Troca de mensagens |
| ⋯→ | **Association** | Associação |

### Exemplo Simples

```
⭕ Início → ▭ Atender Chamado → ◇ Resolvido? 
                                    ↓ Sim → ⭕ Fim
                                    ↓ Não → ▭ Escalar → ⭕ Fim
```

---

## Primeiros Passos

### Acessando o Editor

1. Abra um **Ticket**, **Problema** ou **Mudança**
2. Localize a aba **"FlowBPMN"** no topo
3. Clique na aba para abrir o editor

![Aba FlowBPMN](assets/screenshots/flowbpmn-tab.png)

> **Nota**: Se a aba não aparecer, verifique suas permissões com o administrador.

### Primeiro Diagrama

Vamos criar um diagrama simples de atendimento:

1. **Arraste** um **Start Event** (círculo verde) da paleta à esquerda
2. **Arraste** uma **Task** (retângulo azul)
3. **Arraste** um **End Event** (círculo vermelho)
4. **Conecte** os elementos:
   - Clique no Start Event
   - Clique no ícone de seta que aparece
   - Arraste até a Task
   - Repita para conectar Task ao End Event
5. **Nomeie** os elementos:
   - Clique duplo na Task
   - Digite "Atender Chamado"
   - Pressione Enter
6. **Salve** o diagrama:
   - Clique no botão **"Salvar"** no topo
   - Aguarde a mensagem de sucesso

**Parabéns!** Você criou seu primeiro diagrama BPMN! 🎉

---

## Interface do Editor

### Visão Geral

```
┌─────────────────────────────────────────────────────────┐
│  [Salvar ▼] [Templates] [Versões] [Importar] [Exportar]│ ← Barra de Ferramentas
├────┬────────────────────────────────────────────────────┤
│    │                                                    │
│ P  │                                                    │
│ a  │              Canvas (Área de Trabalho)             │
│ l  │                                                    │
│ e  │                                                    │
│ t  │                                                    │
│ a  │                                                    │
│    │                                                    │
│    │                                          [Mini-Map]│ ← Minimapa
└────┴────────────────────────────────────────────────────┘
```

### Barra de Ferramentas

| Botão | Função |
|-------|--------|
| **Salvar** | Salva o diagrama atual |
| **Salvar como Template** | Salva como modelo reutilizável |
| **Templates** | Abre galeria de modelos |
| **Versões** | Visualiza histórico de versões |
| **Importar** | Importa diagrama de outro item |
| **Exportar** | Exporta para BPMN/SVG/PNG |

### Paleta de Elementos

À esquerda, você encontra todos os elementos BPMN:

- **Eventos**: Start, End, Intermediate
- **Atividades**: Task, Sub-Process
- **Gateways**: Exclusive, Parallel, Inclusive
- **Dados**: Data Object, Data Store
- **Participantes**: Pool, Lane
- **Artefatos**: Group, Annotation

### Canvas (Área de Trabalho)

- **Zoom**: Scroll do mouse ou botões +/-
- **Pan**: Clique e arraste no fundo
- **Seleção**: Clique em elementos
- **Multi-seleção**: Ctrl + Clique ou arraste área

### Mini-Mapa

No canto inferior direito, mostra visão geral do diagrama:
- Útil para navegar em diagramas grandes
- Clique para ir direto a uma área
- Mostra viewport atual

---

## Criando Diagramas

### Adicionando Elementos

#### Método 1: Arrastar da Paleta

1. Localize o elemento na paleta à esquerda
2. Clique e arraste para o canvas
3. Solte no local desejado

#### Método 2: Menu Contextual

1. Clique em um elemento existente
2. Clique no ícone de ferramenta que aparece
3. Selecione o tipo de elemento
4. O novo elemento será conectado automaticamente

### Conectando Elementos

#### Conexão Automática

1. Clique em um elemento
2. Clique no ícone de seta
3. Arraste até o elemento de destino
4. Solte para criar a conexão

#### Conexão Manual

1. Selecione **Sequence Flow** na paleta
2. Clique no elemento de origem
3. Clique no elemento de destino

### Editando Propriedades

#### Nome do Elemento

- **Duplo clique** no elemento
- Digite o nome
- Pressione **Enter**

#### Propriedades Avançadas

1. Clique no elemento
2. Painel de propriedades aparece à direita
3. Edite campos conforme necessário:
   - **Name**: Nome do elemento
   - **Documentation**: Descrição detalhada
   - **ID**: Identificador único (automático)

### Organizando o Diagrama

#### Alinhamento

1. Selecione múltiplos elementos (Ctrl + Clique)
2. Clique direito
3. Escolha opção de alinhamento:
   - Alinhar à esquerda
   - Alinhar ao centro
   - Alinhar à direita
   - Distribuir horizontalmente

#### Espaçamento

- Use **Grid** (grade) para alinhamento preciso
- Ative em: Configurações → Grid → Ativar
- Tamanho padrão: 10px

### Desfazer/Refazer

- **Desfazer**: Ctrl + Z
- **Refazer**: Ctrl + Y ou Ctrl + Shift + Z

### Salvando o Diagrama

1. Clique em **"Salvar"** na barra de ferramentas
2. Aguarde a mensagem de confirmação
3. O diagrama é salvo automaticamente:
   - **BPMN XML** no banco de dados
   - **PNG** anexado ao item
   - **Versão** criada no histórico

> **Auto-save**: O plugin salva automaticamente a cada 5 minutos (se houver alterações).

---

## Gerenciando Versões

O FlowBPMN mantém um histórico completo de todas as alterações no diagrama.

### Visualizando Histórico

1. Clique em **"Versões"** na barra de ferramentas
2. Modal com lista de versões aparece
3. Cada versão mostra:
   - **Miniatura** do diagrama
   - **Data e hora** da criação
   - **Usuário** que criou
   - **Ações** disponíveis

![Modal de Versões](assets/screenshots/versions-modal.png)

### Restaurando Versão Anterior

1. Abra o modal de **Versões**
2. Localize a versão desejada
3. Clique em **"Restaurar"**
4. Confirme a ação
5. O diagrama atual é salvo como backup
6. A versão selecionada é carregada no editor

> **Importante**: Restaurar uma versão cria uma nova versão (não sobrescreve).

### Deletando Versões

1. Abra o modal de **Versões**
2. Localize a versão a deletar
3. Clique em **"Deletar"**
4. Confirme a ação

> **Nota**: Não é possível deletar a versão atual.

### Limite de Versões

- Por padrão, são mantidas as **10 versões mais recentes**
- Versões antigas são deletadas automaticamente
- Administradores podem alterar este limite

---

## Trabalhando com Templates

Templates são diagramas reutilizáveis que você pode aplicar em diferentes itens.

### Salvando como Template

1. Crie ou edite um diagrama
2. Clique na **seta** ao lado do botão "Salvar"
3. Selecione **"Salvar como Template"**
4. Digite um **nome** para o template
5. (Opcional) Adicione uma **descrição**
6. Clique em **"Salvar"**

![Salvar Template](assets/screenshots/save-template.png)

### Carregando Template

1. Clique em **"Templates"** na barra de ferramentas
2. Modal com galeria de templates aparece
3. Use a **barra de busca** para filtrar por nome
4. Clique em **"Aplicar"** no template desejado
5. Confirme a ação (sobrescreve diagrama atual)
6. Template é carregado no editor

![Galeria de Templates](assets/screenshots/templates-gallery.png)

### Buscando Templates

- Digite palavras-chave na barra de busca
- Busca em tempo real
- Filtra por nome do template
- Clique no **X** para limpar a busca

### Deletando Templates

1. Abra a galeria de **Templates**
2. Localize o template a deletar
3. Clique em **"Deletar"**
4. Confirme a ação

> **Permissão**: Você só pode deletar templates que criou (ou se tiver permissão global).

### Templates Públicos vs Privados

- **Privados**: Visíveis apenas para você
- **Públicos**: Visíveis para toda organização (futuro)

---

## Importando Diagramas

Reutilize diagramas de outros Tickets, Problemas ou Mudanças.

### Como Importar

1. Clique em **"Importar"** na barra de ferramentas
2. Modal de importação aparece com 3 abas:
   - **Chamados** (Tickets)
   - **Problemas** (Problems)
   - **Mudanças** (Changes)
3. Selecione a aba do tipo de item
4. Use a **barra de busca** para filtrar
5. Navegue pelas páginas se necessário
6. Clique em **"Importar"** no diagrama desejado
7. Confirme a ação
8. Diagrama é carregado no editor

![Modal de Importação](assets/screenshots/import-modal.png)

### Busca de Diagramas

- Digite número do item (ex: "#123")
- Busca em tempo real
- Filtra por ID do item
- Mostra apenas itens com diagramas

### Paginação

- 6 itens por página
- Use setas **< >** para navegar
- Ou clique nos números de página

---

## Exportando Diagramas

Exporte seus diagramas em diferentes formatos.

### Formatos Disponíveis

| Formato | Extensão | Uso |
|---------|----------|-----|
| **BPMN XML** | `.bpmn` | Interoperabilidade com outras ferramentas |
| **SVG** | `.svg` | Gráficos vetoriais escaláveis |
| **PNG** | `.png` | Imagens raster para apresentações |
| **PDF** | `.pdf` | Documentos imprimíveis |

### Exportar para BPMN XML

1. Clique em **"Exportar"**
2. Selecione **"BPMN XML"**
3. Arquivo `.bpmn` é baixado
4. Pode ser aberto em:
   - Camunda Modeler
   - Bizagi Modeler
   - Outras ferramentas BPMN

### Exportar para SVG

1. Clique em **"Exportar"**
2. Selecione **"SVG"**
3. Arquivo `.svg` é baixado
4. Ideal para:
   - Documentação técnica
   - Wikis
   - Websites

### Exportar para PNG

1. Clique em **"Exportar"**
2. Selecione **"PNG"**
3. Arquivo `.png` é baixado
4. Ideal para:
   - Apresentações PowerPoint
   - Relatórios Word
   - E-mails

### Exportar para PDF

1. Clique em **"Exportar"**
2. Selecione **"PDF"**
3. Janela de impressão do navegador abre
4. Selecione **"Salvar como PDF"**
5. Configure opções:
   - Orientação: Paisagem
   - Margens: Mínimas
6. Clique em **"Salvar"**

---

## Boas Práticas

### Nomenclatura

✅ **Bom**:
- "Atender Chamado de Usuário"
- "Analisar Causa Raiz"
- "Aprovar Mudança"

❌ **Ruim**:
- "Tarefa 1"
- "Processo"
- "Atividade"

### Organização

- **Use Pools e Lanes** para separar responsabilidades
- **Agrupe** elementos relacionados
- **Alinhe** elementos para melhor legibilidade
- **Evite** cruzamento de linhas

### Versionamento

- **Salve** frequentemente
- **Crie versões** em marcos importantes
- **Delete** versões antigas desnecessárias
- **Documente** mudanças significativas

### Templates

- **Crie templates** para processos recorrentes
- **Use nomes descritivos** para templates
- **Mantenha** biblioteca organizada
- **Revise** templates periodicamente

### Performance

- **Evite** diagramas muito complexos (>50 elementos)
- **Divida** processos grandes em sub-processos
- **Use** referências a outros diagramas
- **Limpe** versões antigas

---

## Perguntas Frequentes

### Como faço para...

#### ...criar um diagrama?

1. Abra um Ticket/Problema/Mudança
2. Clique na aba FlowBPMN
3. Arraste elementos da paleta
4. Conecte os elementos
5. Clique em Salvar

#### ...restaurar uma versão anterior?

1. Clique em "Versões"
2. Localize a versão desejada
3. Clique em "Restaurar"
4. Confirme

#### ...compartilhar um diagrama?

- **Opção 1**: Salve como template e outros podem aplicá-lo
- **Opção 2**: Exporte para PNG/PDF e envie por e-mail
- **Opção 3**: Outros usuários podem importar do seu ticket

#### ...deletar um diagrama?

1. Abra o diagrama
2. Delete todos os elementos (Ctrl + A, Delete)
3. Salve
4. Ou peça ao administrador para deletar via banco de dados

### Problemas Comuns

#### Editor não carrega

- Verifique conexão com internet
- Limpe cache do navegador (Ctrl + F5)
- Verifique se JavaScript está habilitado
- Contate administrador

#### Não consigo salvar

- Verifique se tem permissão de "Edit"
- Verifique se o item não está fechado
- Tente novamente em alguns segundos
- Contate administrador

#### Diagrama desapareceu

- Verifique em "Versões" se há backup
- Restaure a versão anterior
- Contate administrador

#### Template não aparece

- Verifique se salvou corretamente
- Use a busca para localizar
- Verifique permissões
- Contate administrador

---

## Atalhos de Teclado

| Atalho | Ação |
|--------|------|
| **Ctrl + Z** | Desfazer |
| **Ctrl + Y** | Refazer |
| **Ctrl + A** | Selecionar tudo |
| **Delete** | Deletar selecionados |
| **Ctrl + C** | Copiar |
| **Ctrl + V** | Colar |
| **Ctrl + S** | Salvar |
| **+** | Zoom in |
| **-** | Zoom out |
| **0** | Zoom 100% |
| **F** | Ajustar ao canvas |

---

## Glossário BPMN

| Termo | Definição |
|-------|-----------|
| **Activity** | Trabalho executado (Task, Sub-Process) |
| **Event** | Algo que acontece (Start, End, Intermediate) |
| **Gateway** | Ponto de decisão ou sincronização |
| **Sequence Flow** | Ordem de execução das atividades |
| **Pool** | Participante do processo |
| **Lane** | Sub-divisão de responsabilidade |
| **Artifact** | Informação adicional (Annotation, Group) |

---

## Recursos Adicionais

### Documentação

- [Guia de Instalação](INSTALLATION.md)
- [Guia do Administrador](ADMIN_GUIDE.md)
- [Guia do Desenvolvedor](DEVELOPER_GUIDE.md)
- [Referência da API](API_REFERENCE.md)

### Tutoriais

- [Vídeo: Primeiros Passos](https://youtube.com/...)
- [Vídeo: Templates Avançados](https://youtube.com/...)
- [Vídeo: Boas Práticas BPMN](https://youtube.com/...)

### Suporte

- [GitHub Issues](https://github.com/diegojucah/FlowBPMN/issues)
- [Fórum GLPI](https://forum.glpi-project.org/)
- [Documentação BPMN](https://www.bpmn.org/)

---

**Aproveite o FlowBPMN e documente seus processos!** 📊🚀

Para dúvidas ou sugestões, visite nosso [GitHub](https://github.com/diegojucah/FlowBPMN).
