# 🧪 Guia de Teste - flowBPMN Plugin

## 📋 Pré-requisitos

- GLPI 11.0+ rodando
- PHP 8.0+
- Acesso ao Docker (se aplicável)
- Acesso admin no GLPI

---

## 🐳 Teste no Docker

### 1. Copiar Plugin para Container

```bash
# No host (onde está o código)
cd /home/diego/glpi11

# Copiar para o container
sudo docker cp flowBPMN 4a5500931c39:/var/www/glpi/plugins/

# Ajustar permissões dentro do container
sudo docker exec -it 4a5500931c39 bash -c "chown -R www-data:www-data /var/www/glpi/plugins/flowBPMN && chmod -R 755 /var/www/glpi/plugins/flowBPMN"
```

### 2. Verificar Arquivos

```bash
# Entrar no container
sudo docker exec -it 4a5500931c39 bash

# Verificar plugin
cd /var/www/glpi/plugins/flowBPMN
ls -la

# Verificar setup.php existe
cat setup.php | head -20
```

### 3. Testar via CLI

```bash
# Dentro do container
cd /var/www/glpi

# Listar plugins
php bin/console glpi:plugin:list

# Instalar plugin
php bin/console glpi:plugin:install flowbpmn

# Ativar plugin
php bin/console glpi:plugin:activate flowbpmn

# Verificar tabelas criadas
php bin/console db:configure --reconfigure
```

---

## 🌐 Teste via Interface Web

### 1. Acessar GLPI

```
URL: http://localhost:8080
Login: glpi (admin padrão)
Senha: glpi
```

### 2. Instalar Plugin

1. Ir em: **Configurar → Plugins**
2. Procurar: **"BPMN Flow"** ou **"flowbpmn"**
3. Clicar em: **Instalar** (ícone de download/caixa)
4. Aguardar mensagem de sucesso
5. Clicar em: **Ativar** (ícone de power/plug)

**✅ Esperado:** Mensagem verde "Plugin instalado com sucesso"

**❌ Se falhar:** Verificar logs

```bash
# No container
tail -f /var/www/glpi/files/_log/php-errors.log
tail -f /var/www/glpi/files/_log/sql-errors.log
```

### 3. Verificar Instalação

```bash
# Dentro do container ou via MySQL client
mysql -u root -p glpi

# Verificar tabelas criadas
SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';

# Deve mostrar:
# glpi_plugin_flowbpmn_configs
# glpi_plugin_flowbpmn_flows
# glpi_plugin_flowbpmn_profiles
# glpi_plugin_flowbpmn_versions

# Verificar dados iniciais
SELECT * FROM glpi_plugin_flowbpmn_configs;
SELECT * FROM glpi_plugin_flowbpmn_profiles;

# Sair
EXIT;
```

### 4. Configurar Permissões

1. Ir em: **Configurar → Perfis**
2. Clicar em: **Super-Admin**
3. Procurar aba: **"BPMN Flow"**
4. Marcar TODAS permissões:
   - ✅ View (Ticket, Problem, Change)
   - ✅ Edit (Ticket, Problem, Change)
   - ✅ Delete (Ticket, Problem, Change)
   - ✅ Restore (Ticket, Problem, Change)
5. Clicar: **Salvar**

### 5. Testar Editor BPMN

1. **Criar Chamado:**
   - Ir em: **Assistência → Chamados**
   - Clicar: **Criar chamado**
   - Preencher:
     - Título: "Teste BPMN Plugin"
     - Descrição: "Teste de instalação"
   - Salvar

2. **Abrir Aba BPMN:**
   - No chamado criado, clicar aba: **"BPMN Flow"**
   - **✅ Esperado:** Editor deve carregar (aguardar 2-5 segundos)
   - **❌ Se não carregar:** Abrir console do navegador (F12) e verificar erros

3. **Criar Diagrama:**
   - Arrastar **Start Event** (círculo verde) da paleta esquerda
   - Arrastar **Task** (retângulo)
   - Arrastar **End Event** (círculo vermelho com borda grossa)
   - Conectar elementos clicando e arrastando setas
   - Preencher campo "Flow Name": "Meu Primeiro Fluxo"
   - Clicar botão: **"Save"**
   - **✅ Esperado:** Mensagem "BPMN diagram saved successfully!"

4. **Verificar Salvamento:**
   - Recarregar página (F5)
   - Verificar se diagrama continua lá
   - Verificar se nome aparece em "Flow Name"

---

## 🧹 Teste de Desinstalação

### Via Interface

1. Ir em: **Configurar → Plugins**
2. Encontrar: **"BPMN Flow"**
3. Clicar: **Desativar** (ícone de power)
4. Aguardar confirmação
5. Clicar: **Desinstalar** (ícone de lixeira)
6. Confirmar exclusão
7. **✅ Esperado:** Plugin removido, tabelas deletadas

### Via CLI

```bash
# Dentro do container
cd /var/www/glpi

# Desativar
php bin/console glpi:plugin:deactivate flowbpmn

# Desinstalar
php bin/console glpi:plugin:uninstall flowbpmn

# Verificar remoção
php bin/console glpi:plugin:list | grep flowbpmn
# Não deve aparecer mais

# Verificar tabelas removidas
mysql -u root -p glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';"
# Não deve retornar nada
```

---

## ⚠️ Troubleshooting

### Problema 1: Plugin não aparece na lista

**Possíveis causas:**
- Permissões incorretas
- Arquivos corrompidos
- Nome do diretório errado

**Solução:**
```bash
# Verificar nome do diretório
ls -la /var/www/glpi/plugins/ | grep -i bpmn

# Deve ser exatamente: flowBPMN

# Se nome diferente, renomear
cd /var/www/glpi/plugins
mv flowBPMN flowbpmn  # GLPI é case-sensitive

# Ajustar permissões
chown -R www-data:www-data flowbpmn
chmod -R 755 flowbpmn
```

### Problema 2: Erro ao instalar

**Verificar logs:**
```bash
# Logs PHP
tail -f /var/www/glpi/files/_log/php-errors.log

# Logs SQL
tail -f /var/www/glpi/files/_log/sql-errors.log

# Logs Apache (se aplicável)
tail -f /var/log/apache2/error.log
```

**Verificar requisitos:**
```bash
# Versão PHP
php -v
# Deve ser >= 8.0

# Versão GLPI
cd /var/www/glpi
php bin/console glpi:system:check_requirements
```

### Problema 3: Editor não carrega

**Abrir console do navegador (F12):**
- Procurar erros em vermelho
- Verificar se CDN está acessível:
  - `https://cdn.jsdelivr.net/npm/bpmn-js@18.6.1/`

**Verificar conectividade:**
```bash
# No container
curl -I https://cdn.jsdelivr.net/npm/bpmn-js@18.6.1/dist/bpmn-modeler.development.js
# Deve retornar HTTP 200
```

### Problema 4: Erro ao salvar

**Verificar permissões de perfil:**
1. Configurar → Perfis → [Seu perfil]
2. Aba BPMN Flow
3. Verificar se "Edit" está marcado para Ticket

**Verificar logs AJAX:**
- Abrir DevTools (F12)
- Aba Network
- Tentar salvar
- Procurar requisição para `ajax/flow.php`
- Ver resposta (deve ser JSON com `success: true`)

---

## 📊 Checklist de Validação

- [ ] Plugin aparece na lista de plugins
- [ ] Instalação completa sem erros
- [ ] 4 tabelas criadas no banco
- [ ] Perfis com permissões configuradas
- [ ] Aba "BPMN Flow" aparece em Chamado/Problema/Mudança
- [ ] Editor carrega corretamente
- [ ] Paleta de elementos visível
- [ ] Consegue arrastar e conectar elementos
- [ ] Botão "Save" funciona
- [ ] Diagrama persiste após recarregar
- [ ] Desativação funciona
- [ ] Desinstalação remove tabelas

---

## ✅ Teste Completo Passou?

Se TODOS os itens do checklist passaram, o plugin está **100% funcional**! 🎉

Próximo passo: **Publicar no GitHub** e criar release.

---

## 🐛 Reportar Problemas

Se encontrar bugs durante os testes:

1. Coletar informações:
   ```bash
   # Versão GLPI
   cat /var/www/glpi/version.txt
   
   # Versão PHP
   php -v
   
   # Logs relevantes
   tail -100 /var/www/glpi/files/_log/php-errors.log
   ```

2. Descrever:
   - O que tentou fazer
   - O que esperava acontecer
   - O que realmente aconteceu
   - Logs de erro

3. Compartilhar para correção
