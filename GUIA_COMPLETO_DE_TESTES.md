# 🎯 GUIA COMPLETO DE TESTES - Plugin flowBPMN

## ✅ STATUS DA SINCRONIZAÇÃO

**Data/Hora:** 12/11/2025 - 14:25 BRT
**Container ID:** 4a5500931c39
**Status:** ✅ TODOS OS ARQUIVOS SINCRONIZADOS COM SUCESSO

### Arquivos Copiados e Verificados

| Arquivo | Status | Sintaxe PHP | Última Modificação |
|---------|--------|-------------|-------------------|
| `ajax/flow.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:15 |
| `ajax/bpmn_versions.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:04 |
| `ajax/bpmn_restore.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:04 |
| `inc/flow.class.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:15 |
| `inc/config.class.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:03 |
| `inc/profile.class.php` | ✅ OK | ✅ SEM ERROS | 12/11 14:02 |
| `inc/version.class.php` | ✅ OK | ✅ SEM ERROS | Original |
| `setup.php` | ✅ OK | ✅ SEM ERROS | Atualizado |
| `locales/pt_BR.php` | ✅ OK | ✅ SEM ERROS | Atualizado |
| `front/config.form.php` | ✅ OK | ✅ SEM ERROS | Original |

**Permissões:** ✅ Ajustadas para `www-data:www-data`

---

## 🔧 MELHORIAS IMPLEMENTADAS

### 1. Sistema de Salvamento PNG Corrigido
**Arquivo:** `inc/flow.class.php` (linhas 326-446)

#### O que foi corrigido:
- ✅ Salvamento de PNG agora usa estrutura correta do GLPI 11
- ✅ Simulação de upload via array `$_FILES`
- ✅ Adicionado campo `entities_id` para vincular à entidade correta
- ✅ Logs de erro detalhados para debugging
- ✅ Tratamento de erro melhorado

#### Como funciona:
```php
1. Usuário salva o diagrama BPMN
2. Sistema gera PNG a partir do SVG (JavaScript)
3. PNG é enviado em base64 para o servidor
4. PHP decodifica e salva no GLPI_TMP_DIR
5. Documento é criado e vinculado ao item (Ticket/Problem/Change)
6. PNG aparece na lista de documentos anexados
```

### 2. Sistema de Logs e Validação
**Arquivo:** `ajax/flow.php` (linhas 28-73)

#### Melhorias:
- ✅ Logs de debug em cada etapa do salvamento
- ✅ Validação de tipo de item (Ticket, Problem, Change)
- ✅ Mensagens de erro mais claras
- ✅ Log de sucesso com ID do fluxo salvo

#### Logs disponíveis:
```bash
# Ver logs do Apache
sudo docker exec 4a5500931c39 tail -f /var/log/apache2/error.log | grep flowBPMN

# Ver logs do PHP
sudo docker exec 4a5500931c39 tail -f /var/log/php/error.log | grep flowBPMN
```

### 3. Sistema de Traduções PT_BR
**Arquivos:** `setup.php` + `locales/pt_BR.php`

#### O que foi corrigido:
- ✅ Carregamento automático de traduções na inicialização
- ✅ Compatibilidade com função `__()` do GLPI
- ✅ Todas as permissões traduzidas:
  - View → **Visualizar**
  - Edit → **Editar**
  - Delete → **Excluir**
  - Restore → **Restaurar**
- ✅ Título corrigido: **"Gerenciamento de Permissões flowBPMN"**

### 4. Ícones Padronizados GLPI 10/11
**Arquivos:** `inc/flow.class.php` + `inc/profile.class.php`

#### Ícones por versão:
- **GLPI 11.x:** `ti ti-git-fork` (Tabler Icons)
- **GLPI 10.x:** `fas fa-project-diagram` (Font Awesome)

#### Onde aparecem:
- ✅ Aba flowBPMN em Tickets
- ✅ Aba flowBPMN em Problemas
- ✅ Aba flowBPMN em Mudanças
- ✅ Aba flowBPMN em Perfis
- ✅ Menu de Configuração

### 5. Sistema de Versionamento
**Arquivos:** `ajax/bpmn_versions.php` + `ajax/bpmn_restore.php`

#### Funcionalidades:
- ✅ Histórico completo de versões
- ✅ Restauração de versões anteriores
- ✅ Backup automático ao restaurar
- ✅ Limite configurável de versões
- ✅ Informações detalhadas (usuário, data, nome)

---

## 🧪 ROTEIRO DE TESTES

### Teste 1: Acesso à Configuração do Plugin
```
URL: http://localhost:8080/plugins/flowbpmn/front/config.form.php

Resultado Esperado:
- ✅ Página carrega sem erros
- ✅ Formulário de configuração aparece
- ✅ Opções disponíveis:
  - Anexar diagrama automaticamente
  - Máximo de versões por fluxo
  - Habilitar exportações (BPMN, SVG, PNG)
```

**Status:** ⏳ AGUARDANDO TESTE

---

### Teste 2: Verificar Permissões em Perfis
```
Caminho: Configuração → Perfis → [Seu Perfil] → Aba "flowBPMN"

Resultado Esperado:
- ✅ Aba "flowBPMN" aparece com ícone correto
- ✅ Título: "Gerenciamento de Permissões flowBPMN"
- ✅ Tabela com colunas em PT_BR:
  - Tipo de Item
  - Visualizar
  - Editar
  - Excluir
  - Restaurar
- ✅ Linhas para: Ticket, Problem, Change
```

**Status:** ⏳ AGUARDANDO TESTE

---

### Teste 3: Criar e Salvar Diagrama em Ticket

#### 3.1 Criar Ticket
```
1. Acesse: Assistência → Tickets
2. Clique em "+" para criar novo ticket
3. Preencha:
   - Título: "Teste Plugin flowBPMN"
   - Descrição: "Testando salvamento de diagrama"
4. Clique em "Adicionar"
```

#### 3.2 Acessar Aba flowBPMN
```
1. Abra o ticket criado
2. Clique na aba "flowBPMN"

Resultado Esperado:
- ✅ Aba aparece com ícone (ti ti-git-fork ou fas fa-project-diagram)
- ✅ Editor BPMN carrega
- ✅ Toolbar aparece com botões:
  - Salvar
  - PNG / SVG / BPMN (exportação)
```

#### 3.3 Desenhar Diagrama
```
1. Clique na área do canvas
2. Arraste elementos da paleta:
   - Start Event (círculo verde)
   - Task (retângulo)
   - End Event (círculo vermelho)
3. Conecte os elementos
4. Dê nome às tarefas (duplo clique)
```

#### 3.4 Salvar Diagrama
```
1. Clique no botão "Salvar"
2. Aguarde mensagem de sucesso

Resultado Esperado:
- ✅ Mensagem: "Fluxo flowBPMN salvo com sucesso!"
- ✅ Diagrama permanece na tela
- ✅ Botão "Versões" aparece na toolbar
```

#### 3.5 Verificar PNG Anexado
```
1. Role a página até a seção "Documentos"
2. Procure por documento recente

Resultado Esperado:
- ✅ Novo documento PNG aparece
- ✅ Nome: "Diagrama flowBPMN" ou similar
- ✅ Data: Hoje
- ✅ Ao clicar, PNG do diagrama é exibido
```

**Status:** ⏳ AGUARDANDO TESTE

---

### Teste 4: Testar Versionamento

#### 4.1 Modificar Diagrama
```
1. No ticket com diagrama salvo
2. Modifique o diagrama (adicione uma task)
3. Clique em "Salvar"
```

#### 4.2 Acessar Histórico
```
1. Clique no botão "Versões"

Resultado Esperado:
- ✅ Modal abre com título "Histórico de Versões - flowBPMN"
- ✅ Mostra versão atual
- ✅ Lista versões anteriores com:
  - Número da versão
  - Nome/Comentário
  - Data de criação
  - Nome do usuário
  - Botão "Restaurar"
```

#### 4.3 Restaurar Versão
```
1. Clique em "Restaurar" em uma versão anterior
2. Confirme a ação

Resultado Esperado:
- ✅ Mensagem: "Versão restaurada com sucesso!"
- ✅ Diagrama é recarregado com versão anterior
- ✅ Modal fecha automaticamente
- ✅ Página recarrega após 1.5 segundos
```

**Status:** ⏳ AGUARDANDO TESTE

---

### Teste 5: Testar em Problema e Mudança

```
Repita os Testes 3 e 4 para:
- Assistência → Problemas
- Assistência → Mudanças

Resultado Esperado:
- ✅ Funciona idêntico aos Tickets
- ✅ Ícone correto na aba
- ✅ Salvamento funciona
- ✅ PNG é anexado
- ✅ Versionamento funciona
```

**Status:** ⏳ AGUARDANDO TESTE

---

### Teste 6: Testar Exportações

#### 6.1 Exportar PNG
```
1. No editor BPMN, clique no botão "PNG"

Resultado Esperado:
- ✅ Download de arquivo diagram.png inicia
- ✅ PNG contém o diagrama completo
```

#### 6.2 Exportar SVG
```
1. Clique no botão "SVG"

Resultado Esperado:
- ✅ Download de arquivo diagram.svg inicia
- ✅ SVG pode ser aberto em navegador/editor
```

#### 6.3 Exportar BPMN XML
```
1. Clique no botão "BPMN"

Resultado Esperado:
- ✅ Download de arquivo diagram.bpmn inicia
- ✅ Arquivo XML contém definição BPMN válida
```

**Status:** ⏳ AGUARDANDO TESTE

---

## 🐛 TROUBLESHOOTING

### Problema: Página config.form.php não carrega

**Solução 1: Verificar se plugin está ativado**
```bash
# Acessar GLPI → Configuração → Plugins
# Verificar se "flowBPMN" está com status "Instalado e Ativado"
```

**Solução 2: Verificar logs de erro**
```bash
sudo docker exec 4a5500931c39 tail -50 /var/log/apache2/error.log
```

**Solução 3: Verificar permissões**
```bash
sudo docker exec 4a5500931c39 ls -la /var/www/glpi/plugins/flowbpmn/front/
```

---

### Problema: PNG não é anexado ao salvar

**Diagnóstico:**
```bash
# Ver logs do flowBPMN
sudo docker exec 4a5500931c39 tail -f /var/log/apache2/error.log | grep flowBPMN

# Verificar diretório temporário
sudo docker exec 4a5500931c39 ls -la /tmp/
```

**Possíveis causas:**
1. ❌ Configuração "Anexar diagrama automaticamente" desativada
2. ❌ Permissões do diretório GLPI_TMP_DIR
3. ❌ PNG não está sendo gerado no JavaScript

**Solução:**
```bash
# 1. Verificar configuração
Acessar: http://localhost:8080/plugins/flowbpmn/front/config.form.php
Marcar: "Anexar diagrama BPMN automaticamente ao item" = Sim

# 2. Verificar permissões
sudo docker exec 4a5500931c39 chmod 777 /tmp/

# 3. Abrir Console do Navegador (F12)
# Procurar por erros JavaScript na aba "Console"
```

---

### Problema: Traduções não aparecem em PT_BR

**Diagnóstico:**
```bash
# Verificar arquivo de tradução
sudo docker exec 4a5500931c39 cat /var/www/glpi/plugins/flowbpmn/locales/pt_BR.php | head -30

# Verificar se setup.php carrega traduções
sudo docker exec 4a5500931c39 grep -A 15 "Load translations" /var/www/glpi/plugins/flowbpmn/setup.php
```

**Solução:**
```bash
# Re-sincronizar arquivos
cd /home/diego/glpi11/flowbpmn
./sync_to_docker.sh

# Limpar cache do GLPI
Acessar: Configuração → Ações → Limpar cache
```

---

### Problema: Botão "Versões" não aparece

**Causa:** Ainda não foi salvo nenhum fluxo

**Solução:**
1. Desenhe um diagrama
2. Salve (botão "Salvar")
3. Aguarde confirmação de sucesso
4. Botão "Versões" aparecerá automaticamente

---

### Problema: Erro ao restaurar versão

**Diagnóstico:**
```bash
# Ver logs
sudo docker exec 4a5500931c39 tail -20 /var/log/apache2/error.log
```

**Possível causa:** Permissão negada

**Solução:**
```bash
# Verificar permissões do perfil
Acessar: Configuração → Perfis → [Seu Perfil] → Aba flowBPMN
Marcar: "Restaurar" = Sim para Ticket/Problem/Change
```

---

## 📊 CHECKLIST FINAL

### Antes de Testar
- [x] ✅ Todos os arquivos sincronizados com Docker
- [x] ✅ Sintaxe PHP verificada (sem erros)
- [x] ✅ Permissões ajustadas (www-data:www-data)
- [ ] ⏳ Plugin ativado no GLPI
- [ ] ⏳ Permissões de perfil configuradas

### Testes Funcionais
- [ ] ⏳ Config.form.php carrega sem erros
- [ ] ⏳ Traduções aparecem em PT_BR
- [ ] ⏳ Ícones corretos nas abas
- [ ] ⏳ Editor BPMN carrega corretamente
- [ ] ⏳ Salvamento funciona (mensagem de sucesso)
- [ ] ⏳ PNG é anexado automaticamente
- [ ] ⏳ Botão "Versões" aparece após salvar
- [ ] ⏳ Histórico de versões funciona
- [ ] ⏳ Restauração de versão funciona
- [ ] ⏳ Exportações (PNG/SVG/BPMN) funcionam

### Testes em Diferentes Itens
- [ ] ⏳ Funciona em Ticket
- [ ] ⏳ Funciona em Problem
- [ ] ⏳ Funciona em Change

---

## 🎉 PRÓXIMOS PASSOS

1. **Acessar o GLPI**
   ```
   URL: http://localhost:8080
   ```

2. **Verificar Plugin Ativado**
   ```
   Configuração → Plugins → flowBPMN
   ```

3. **Configurar Permissões**
   ```
   Configuração → Perfis → [Seu Perfil] → flowBPMN
   Marcar todas as permissões como Sim
   ```

4. **Testar Configuração**
   ```
   http://localhost:8080/plugins/flowbpmn/front/config.form.php
   ```

5. **Criar Ticket de Teste**
   ```
   Assistência → Tickets → + Novo
   ```

6. **Desenhar e Salvar Diagrama**
   ```
   Aba flowBPMN → Desenhar → Salvar
   ```

7. **Verificar PNG Anexado**
   ```
   Seção Documentos do ticket
   ```

8. **Testar Versionamento**
   ```
   Modificar diagrama → Salvar → Versões
   ```

---

## 📝 NOTAS IMPORTANTES

### Performance
- Editor BPMN carrega via CDN (bpmn-js v18.6.1)
- Primeira vez pode demorar ~2-3 segundos
- Após carregado, fica em cache do navegador

### Compatibilidade
- ✅ GLPI 10.0+ até 11.99.99
- ✅ PHP 7.4+
- ✅ Navegadores modernos (Chrome, Firefox, Edge, Safari)

### Limitações
- PNG máximo: depende da memória PHP
- Versões por fluxo: configurável (padrão 10)
- Editor requer JavaScript ativado

### Segurança
- ✅ CSRF protection ativado
- ✅ Validação de permissões
- ✅ Sanitização de entrada
- ✅ Session check em todos os AJAX

---

## 📞 SUPORTE

### Logs Úteis
```bash
# Apache Error Log
sudo docker exec 4a5500931c39 tail -f /var/log/apache2/error.log

# PHP Error Log (se existir)
sudo docker exec 4a5500931c39 tail -f /var/log/php/error.log

# Filtrar apenas flowBPMN
sudo docker exec 4a5500931c39 tail -f /var/log/apache2/error.log | grep -i flowbpmn
```

### Comandos Úteis
```bash
# Re-sincronizar arquivos
cd /home/diego/glpi11/flowbpmn && ./sync_to_docker.sh

# Verificar sintaxe PHP
php -l arquivo.php

# Verificar status do container
sudo docker ps | grep glpi

# Reiniciar Apache no container
sudo docker exec 4a5500931c39 service apache2 restart
```

---

**Última atualização:** 12/11/2025 - 14:30 BRT
**Versão do Plugin:** 1.0.0
**Desenvolvedor:** KactuX
**Comunidade:** Open Source GLPI
