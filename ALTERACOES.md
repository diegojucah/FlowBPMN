# Alterações Realizadas no Plugin flowBPMN

## Resumo das Correções

### 1. ✅ Config.form.php
- O arquivo estava correto, problema era apenas falta de sincronização com Docker
- URL: `http://localhost:8080/plugins/flowbpmn/front/config.form.php`

### 2. ✅ Ícones Padronizados (GLPI 10/11)
- Código já estava preparado para compatibilidade
- GLPI 11: usa `ti ti-git-fork`
- GLPI 10: usa `fas fa-project-diagram`
- Ícones aplicados em:
  - Aba flowBPMN em Ticket, Problem e Change
  - Aba de configuração de Perfis
  - Menu de configuração

### 3. ✅ Salvamento de Diagrama e Criação de PNG
**Arquivo: `inc/flow.class.php`**
- Melhorado o método `savePNGAsDocument()` para GLPI 11
- Adicionado suporte correto para upload de arquivos no GLPI 11
- Simulação de array `$_FILES` para compatibilidade
- Adicionado campo `entities_id` para vincular documento à entidade
- Melhorado tratamento de erros com logs

**Arquivo: `ajax/flow.php`**
- Adicionado logs de debug para facilitar troubleshooting
- Melhorada validação de entrada de dados
- Adicionado validação de tipo de item (Ticket, Problem, Change)

### 4. ✅ Controle de Versionamento
**Arquivos: `ajax/bpmn_versions.php` e `ajax/bpmn_restore.php`**
- Sistema de versionamento já estava implementado
- O botão "Versões" aparece automaticamente após salvar o primeiro fluxo
- Funcionalidades:
  - Listar todas as versões anteriores
  - Restaurar versão anterior
  - Ao restaurar, a versão atual é salva automaticamente
  - Limite configurável de versões por fluxo

### 5. ✅ Traduções PT_BR
**Arquivo: `locales/pt_BR.php`**
- Corrigido sistema de traduções para funcionar com `__()` do GLPI
- Traduções adicionadas/corrigidas:
  - "View" → "Visualizar"
  - "Edit" → "Editar"
  - "Delete" → "Excluir"
  - "Restore" → "Restaurar"
  - "BPMN Flow Rights Management" → "Gerenciamento de Permissões flowBPMN"
  - "Item Type" → "Tipo de Item"

**Arquivo: `setup.php`**
- Adicionado carregamento automático do arquivo de traduções
- Suporte para múltiplos idiomas (fallback para pt_BR)

## Arquivos Modificados

1. `ajax/bpmn_restore.php` - Sistema de restauração de versões
2. `ajax/bpmn_versions.php` - Listagem de versões
3. `ajax/flow.php` - Salvamento com logs e validações
4. `inc/config.class.php` - Configurações do plugin
5. `inc/flow.class.php` - Melhorias no salvamento de PNG
6. `inc/profile.class.php` - Gerenciamento de permissões
7. `locales/pt_BR.php` - Traduções corrigidas
8. `setup.php` - Carregamento de traduções

## Como Sincronizar com Docker

Execute o script de sincronização:

```bash
cd /home/diego/glpi11/flowbpmn
./sync_to_docker.sh
```

Ou copie manualmente os arquivos:

```bash
# Lista de arquivos modificados
sudo docker cp ajax/bpmn_restore.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/ajax/
sudo docker cp ajax/bpmn_versions.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/ajax/
sudo docker cp ajax/flow.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/ajax/
sudo docker cp inc/config.class.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/inc/
sudo docker cp inc/flow.class.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/inc/
sudo docker cp inc/profile.class.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/inc/
sudo docker cp locales/pt_BR.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/locales/
sudo docker cp setup.php 4a5500931c39:/var/www/glpi/plugins/flowbpmn/

# Ajustar permissões
sudo docker exec 4a5500931c39 bash -c "chown -R www-data:www-data /var/www/glpi/plugins/flowbpmn"
```

## Funcionalidades Corrigidas

### Salvamento de Fluxo
1. Usuário desenha o diagrama BPMN
2. Clica em "Salvar"
3. Sistema:
   - Salva XML do BPMN
   - Salva SVG para visualização
   - Gera e anexa PNG no corpo do chamado/problema/mudança
   - Cria versão automática
4. Mensagem de sucesso: "Fluxo flowBPMN salvo com sucesso!"

### Versionamento
1. Após salvar um fluxo, botão "Versões" aparece
2. Ao clicar, modal mostra:
   - Versão atual
   - Histórico de versões
   - Opção de restaurar versões anteriores (se tiver permissão)
3. Ao restaurar:
   - Versão atual é salva automaticamente
   - Versão escolhida se torna a atual
   - Diagrama é recarregado automaticamente

### Permissões
Cada perfil pode ter permissões diferentes para cada tipo de item:
- **Visualizar**: Ver o diagrama BPMN
- **Editar**: Modificar o diagrama
- **Excluir**: Remover o fluxo
- **Restaurar**: Restaurar versões anteriores

## Próximos Passos

1. Execute o script de sincronização ou copie os arquivos manualmente
2. Acesse http://localhost:8080
3. Teste a funcionalidade:
   - Criar/editar um chamado
   - Acessar aba "flowBPMN"
   - Desenhar um diagrama
   - Clicar em "Salvar"
   - Verificar se PNG foi anexado ao chamado
   - Testar botão "Versões"

## Troubleshooting

Se encontrar problemas:

1. Verifique logs do PHP no container:
   ```bash
   sudo docker exec 4a5500931c39 tail -f /var/log/apache2/error.log
   ```

2. Verifique permissões:
   ```bash
   sudo docker exec 4a5500931c39 ls -la /var/www/glpi/plugins/flowbpmn/
   ```

3. Verifique se as traduções foram carregadas acessando:
   - Configuração → Perfis → [Seu Perfil] → Aba "flowBPMN"
   - As permissões devem aparecer em português
