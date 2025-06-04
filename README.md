# Sistema PROEIS - Águas de Juturnaíba

Sistema de acompanhamento para equipes PROEIS (Programa de Redução de Perdas).

## 🚀 Funcionalidades

- ✅ Registro de ordens de serviço
- ✅ Controle de irregularidades
- ✅ Relatórios gerenciais
- ✅ Logs de acesso de usuários
- ✅ Impressão de ordens
- ✅ Dashboard com estatísticas

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)

## 🔧 Instalação

### 1. Clone o repositório
\`\`\`bash
git clone https://github.com/seu-usuario/ProeisCAJ.git
cd sistema-proeis
\`\`\`

### 2. Configuração do Banco de Dados

**Opção A: Usando variáveis de ambiente (Recomendado)**
\`\`\`bash
cp .env.example .env
# Edite o arquivo .env com suas credenciais
\`\`\`

**Opção B: Configuração direta**
Edite o arquivo `config/database.php` e substitua as variáveis de ambiente pelas suas credenciais:

\`\`\`php
// Substitua esta linha:
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
// Por:
define('DB_HOST', 'seu_host');

// E assim por diante para USER, PASS e NAME
\`\`\`

### 3. Criar o banco de dados
\`\`\`bash
mysql -u seu_usuario -p
CREATE DATABASE seu_banco_db;
USE seu_banco_db;
SOURCE database.sql;
\`\`\`

### 4. Configurar permissões
\`\`\`bash
chmod 755 assets/
chmod 644 config/
\`\`\`

## 👤 Acesso Inicial

- **Usuário:** admin
- **Senha:** admin123

⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro login!

## 🔒 Segurança

- Configure HTTPS em produção
- Use variáveis de ambiente para credenciais
- Faça backup regular do banco de dados
- Monitore os logs de acesso

## 📊 Estrutura do Projeto

\`\`\`
sistema-proeis/
├── config/
│   ├── database.php      # Configurações do banco
│   └── init.php         # Inicialização do sistema
├── includes/
│   ├── functions.php    # Funções auxiliares
│   └── security.php     # Funções de segurança
├── assets/
│   └── css/
│       └── style.css    # Estilos do sistema
├── database.sql         # Script de criação do banco
└── README.md
\`\`\`

## 🤝 Contribuindo

1. Faça um Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📞 Suporte

Para dúvidas ou problemas:
- Abra uma issue no GitHub
- Entre em contato com a equipe de desenvolvimento

---

**Sistema PROEIS** - Desenvolvido para Águas de Juturnaíba
