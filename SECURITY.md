# Política de Segurança

## Versões Suportadas

Apenas a versão mais recente do FlowBPMN recebe atualizações de segurança.

| Versão | Suportada          |
| ------ | ------------------ |
| 2.1.x  | :white_check_mark: |
| 2.0.x  | :x:                |
| < 2.0  | :x:                |

## Vulnerabilidades Conhecidas e Corrigidas

### v2.1.0 - Correção Crítica de Autenticação

**Data**: 26/12/2025

**Severidade**: CRÍTICA

**Descrição**: Versões anteriores à 2.1.0 continham um hardcoded `$user_id = 2` no arquivo `ajax/flow.php`, fazendo com que todos os registros de diagramas BPMN fossem atribuídos ao usuário com ID 2, independente de quem realmente executou a ação.

**Impacto**:
- Impossibilidade de auditoria correta
- Violação de princípios de segurança
- Não conformidade com arquitetura nativa do GLPI
- Possíveis problemas de permissões

**Correção**: 
- Implementado bootstrap correto do GLPI
- Uso de `Session::checkLoginUser()` para autenticação
- Uso de `Session::getLoginUserID()` para identificação do usuário
- Validações de permissão em todas as operações

**Ação Requerida**: Atualizar imediatamente para v2.1.0 ou superior.

## Reportando uma Vulnerabilidade

Se você descobrir uma vulnerabilidade de segurança no FlowBPMN, por favor:

1. **NÃO** abra uma issue pública no GitHub
2. Envie um email para: **diego.juca@kactux.com.br**
3. Inclua:
   - Descrição detalhada da vulnerabilidade
   - Passos para reproduzir
   - Versão afetada
   - Impacto potencial

### O que esperar

- **Confirmação**: Você receberá confirmação do recebimento em até 48 horas
- **Avaliação**: Avaliaremos a vulnerabilidade em até 7 dias
- **Correção**: Se confirmada, trabalharemos em uma correção prioritária
- **Divulgação**: Coordenaremos a divulgação pública após a correção estar disponível
- **Créditos**: Você será creditado pela descoberta (se desejar)

## Boas Práticas de Segurança

### Para Administradores

1. **Mantenha Atualizado**: Sempre use a versão mais recente do plugin
2. **Configure Permissões**: Não dê permissão de "Edit" indiscriminadamente
3. **Revise Logs**: Monitore regularmente quem está criando/modificando diagramas
4. **Backup Regular**: Mantenha backups do banco de dados
5. **HTTPS**: Use sempre HTTPS em produção

### Para Desenvolvedores

1. **Nunca** faça hardcode de IDs de usuários
2. **Sempre** use `Session::getLoginUserID()` para identificar usuários
3. **Sempre** use `Session::checkLoginUser()` para validar autenticação
4. **Sempre** verifique permissões antes de operações sensíveis
5. **Sempre** sanitize inputs do usuário
6. **Sempre** use prepared statements para queries SQL

## Auditoria de Segurança

O plugin FlowBPMN implementa:

- ✅ Autenticação obrigatória em todos os endpoints AJAX
- ✅ Validação de permissões baseada em perfis do GLPI
- ✅ Rastreamento completo de auditoria (quem fez o quê e quando)
- ✅ Sanitização de XML/SVG para prevenir XSS
- ✅ Prepared statements para prevenir SQL injection
- ✅ Verificação de CSRF em operações críticas

## Conformidade

O plugin segue:

- Arquitetura nativa do GLPI
- Padrões PSR-12 de código PHP
- Boas práticas de segurança OWASP
- Princípios de menor privilégio

## Contato

- **Email**: diego.juca@kactux.com.br
- **GitHub**: https://github.com/diegojucah/FlowBPMN
- **Website**: https://kactux.com.br

---

**Última atualização**: 26/12/2025
