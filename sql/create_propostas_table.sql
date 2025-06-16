CREATE TABLE propostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parceiro_id INT NOT NULL,
    numero_proposta VARCHAR(20) UNIQUE,
    nome_cliente VARCHAR(255) NOT NULL,
    razao_social VARCHAR(255),
    cnpj VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    email VARCHAR(255),
    telefone VARCHAR(20),
    contato VARCHAR(255),
    
    -- Detalhes da Proposta
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_validade DATE,
    status_proposta ENUM('Em Elaboração', 'Enviada', 'Aprovada', 'Rejeitada', 'Expirada') DEFAULT 'Em Elaboração',
    valor_total DECIMAL(10,2),
    condicoes_pagamento TEXT,
    prazo_entrega VARCHAR(100),
    observacoes TEXT,
    
    -- Produtos/Serviços da proposta (JSON array)
    itens_proposta JSON,
    
    -- Metadata
    ultima_modificacao DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    FOREIGN KEY (parceiro_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
