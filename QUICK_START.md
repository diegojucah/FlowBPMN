# ⚡ Quick Start - flowBPMN Plugin

## 🚀 Instalação Rápida (5 minutos)

### No Docker

```bash
# 1. Copiar plugin para container
sudo docker cp /home/diego/glpi11/flowBPMN 4a5500931c39:/var/www/glpi/plugins/

# 2. Ajustar permissões
sudo docker exec -it 4a5500931c39 bash -c "chown -R www-data:www-data /var/www/glpi/plugins/flowBPMN && chmod -R 755 /var/www/glpi/plugins/flowBPMN"

# 3. Instalar via CLI
sudo docker exec -it 4a5500931c39 bash -c "cd /var/www/glpi && php bin/console glpi:plugin:install flowbpmn && php bin/console glpi:plugin:activate flowbpmn"
```

### Ou Via Interface Web

1. Acesse: http://localhost:8080
2. Login: glpi / glpi
3. Vá em: **Configurar → Plugins**
4. Encontre: **BPMN Flow**
5. Clique: **Instalar** → **Ativar**

---

## ✅ Verificação Rápida

```bash
# Entrar no container
sudo docker exec -it 4a5500931c39 bash

# Verificar instalação
cd /var/www/glpi
php bin/console glpi:plugin:list | grep flowbpmn

# Deve mostrar:
# flowbpmn | BPMN Flow | 1.0.0 | ENABLED
```

---

## 🧪 Teste Rápido

1. **Criar Chamado:**
   - Assistência → Chamados → Criar
   - Título: "Teste BPMN"
   - Salvar

2. **Abrir Editor:**
   - Aba: **"BPMN Flow"**
   - Aguardar editor carregar (3-5s)

3. **Criar Diagrama:**
   - Arrastar elementos da paleta esquerda
   - Conectar com setas
   - Clicar **"Save"**

✅ **Sucesso!** Se viu mensagem de salvamento, está funcionando!

---

## 🔧 Problemas?

### Plugin não aparece na lista

```bash
# Verificar nome do diretório (deve ser flowBPMN)
ls -la /var/www/glpi/plugins/ | grep -i bpmn

# Se nome errado, renomear
cd /var/www/glpi/plugins
mv flowBPMN flowbpmn
```

### Erro ao instalar

```bash
# Ver logs
tail -50 /var/www/glpi/files/_log/php-errors.log
tail -50 /var/www/glpi/files/_log/sql-errors.log
```

### Editor não carrega

- Abrir console do navegador (F12)
- Verificar erros em vermelho
- Verificar se CDN está acessível: https://cdn.jsdelivr.net

---

## 🆘 Suporte

- **Documentação Completa:** [README.md](README.md)
- **Guia de Teste:** [TEST_INSTALL.md](TEST_INSTALL.md)
- **Issues:** https://github.com/diegojucah/pluginBPMN/issues

---

## 🎯 Próximos Passos

Após instalação bem-sucedida:

1. **Configurar Permissões:**
   - Configurar → Perfis → Super-Admin
   - Aba: BPMN Flow
   - Marcar todas permissões

2. **Configurar Plugin:**
   - Configurar → Gerais → BPMN Flow
   - Ativar auto-anexar imagem
   - Definir máximo de versões

3. **Usar em Produção:**
   - Criar fluxos em Chamados reais
   - Exportar diagramas (BPMN, SVG, PNG)
   - Usar versionamento

**Divirta-se criando fluxos BPMN! 🎉**
