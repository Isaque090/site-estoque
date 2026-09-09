CREATE DATABASE Estoque;
USE Estoque;

-- possivel tabela estoque
-- possivel tabela fornecedor
-- possivel tabela cliente


-- os produtos, o basico tipo comida, limpeza, etc,
CREATE TABLE Produtos(
    cd_produto INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nm_produto VARCHAR(80) NOT NULL,
    vl_produto DECIMAL(10,2) NOT NULL,
    dt_validade_produto DATE NOT NULL,
    ds_produto VARCHAR(255) NOT NULL,
    qt_estoque INT NOT NULL DEFAULT 0
);


CREATE TABLE fornecedores (
    cd_fornecedor INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nm_fornecedor VARCHAR(100) NOT NULL,
    ds_telefone VARCHAR(20) NOT NULL,
    ds_email VARCHAR(100) NOT NULL
);


-- e na onde os produtos vão fica, pq n ´pode deixar bagunçado, exemplo(categorias: comida, limpeza, higiene, congelador
CREATE TABLE Categorias(
    cd_categoria INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nm_categoria VARCHAR(80) NOT NULL,
    ds_categoria VARCHAR(255),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);


-- aqui n preciso falar nada, da pra saber
CREATE TABLE Funcionarios (
    cd_funcionario INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nm_funcionario VARCHAR(100) NOT NULL,
    dt_nascimento DATE NOT NULL,
    ds_telefone VARCHAR(20),
    ds_email VARCHAR(100),
    ds_cargo ENUM('admin', 'vendedor', 'estoquista') DEFAULT 'vendedor',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    ds_senha VARCHAR(155) NOT NULL
);


CREATE TABLE compras (
    cd_compra INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    dt_compra DATETIME NOT NULL,
    vl_total DECIMAL(10,2) NOT NULL,
    id_funcionario INT NOT NULL,
    id_fornecedor INT NOT NULL,

    FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios(cd_funcionario),

    FOREIGN KEY (id_fornecedor)
        REFERENCES fornecedores(cd_fornecedor)
);


-- saida de produtos do estoque
CREATE TABLE vendas (
    cd_venda INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    dt_venda DATETIME NOT NULL,
    vl_total DECIMAL(10,2) NOT NULL,
    id_funcionario INT NOT NULL,

    FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios(cd_funcionario)
);


CREATE TABLE produto_categoria (
    id_produto_categoria INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_produto INT NOT NULL,
    id_categoria INT NOT NULL,

    FOREIGN KEY (id_produto)
        REFERENCES Produtos(cd_produto),

    FOREIGN KEY (id_categoria)
        REFERENCES Categorias(cd_categoria)
);


CREATE TABLE itens_compra (
    id_item_compra INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_compra INT NOT NULL,
    id_produto INT NOT NULL,
    qt_produto INT NOT NULL,
    vl_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_compra)
        REFERENCES Compras(cd_compra),

    FOREIGN KEY (id_produto)
        REFERENCES Produtos(cd_produto)
);


CREATE TABLE itens_venda (
    id_item_venda INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_venda INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    vl_unitario DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (id_venda)
        REFERENCES Vendas(cd_venda),

    FOREIGN KEY (id_produto)
        REFERENCES Produtos(cd_produto)
);


-- inner join
SELECT p.nm_produto, c.nm_categoria
FROM produtos p
INNER JOIN produto_categoria pc 
    ON p.cd_produto = pc.id_produto
INNER JOIN categorias c 
    ON pc.id_categoria = c.cd_categoria;


SELECT c.cd_compra, p.nm_produto, qt_produto, ic.vl_unitario
FROM compras c
INNER JOIN itens_compra ic 
    ON c.cd_compra = ic.id_compra
INNER JOIN produtos p 
    ON ic.id_produto = p.cd_produto;


-- admin
INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("JoãoLucas", "2010-07-03", "23904-123", "joao.aura@empresario.com", "admin", "123456");

INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("IsaqueSevero", "2009-09-21", "12345-777", "Isaque.severo@empresario.com", "admin", "123456");


-- gerente
INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("JoãoLucas", "2010-07-03", "23904-123", "joao.gerente@empresario.com", "vendedor", "123456");

INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("IsaqueSevero", "2009-09-21", "12345-777", "isaque.gerente@empresario.com", "vendedor", "123456");


-- estoquista
INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("João Lucas", "2010-07-03", "23904-123", "joao.CLT@empresario.com", "estoquista", "123456");

INSERT INTO Funcionarios 
(nm_funcionario, dt_nascimento, ds_telefone, ds_email, ds_cargo, ds_senha) 
VALUES
("Isaque Severo", "2009-09-21", "12345-777", "isaque.CLT@empresario.com", "estoquista", "123456");


-- fornecedores
INSERT INTO fornecedores
(nm_fornecedor, ds_telefone, ds_email)
VALUES
("Tech Distribuidora", "1199999-1111", "contato@techdistribuidora.com");

INSERT INTO fornecedores
(nm_fornecedor, ds_telefone, ds_email)
VALUES
("Mercado Alimentos", "1198888-2222", "contato@mercadoalimentos.com");

INSERT INTO fornecedores
(nm_fornecedor, ds_telefone, ds_email)
VALUES
("Limpeza Brasil", "1197777-3333", "contato@limpezabrasil.com");


-- categorias
INSERT INTO Categorias
(nm_categoria, ds_categoria)
VALUES
("Gamer", "Produtos para computadores e jogos");

INSERT INTO Categorias
(nm_categoria, ds_categoria)
VALUES
("Eletrônicos", "Produtos eletrônicos");

INSERT INTO Categorias
(nm_categoria, ds_categoria)
VALUES
("Alimentos", "Produtos alimentícios");

INSERT INTO Categorias
(nm_categoria, ds_categoria)
VALUES
("Limpeza", "Produtos para limpeza");


-- Produtos
-- Produtos (com quantidade)
INSERT INTO Produtos
(nm_produto, ds_produto, vl_produto, dt_validade_produto, qt_estoque)
VALUES

-- categoria de gamer 1
('Mouse Gamer', 'Mouse com RGB', 89.90, '2030-12-31', 100),

('Teclado Mecânico', 'Teclado switch blue', 249.90, '2030-12-31', 100),

('Monitor Gamer', 'Monitor gamer de alta frequência', 2490.90, '2030-12-31', 50),

('PC Ultra Gamer', 'PC gamer de alto desempenho', 24900.90, '2030-12-31', 10),

('Headset Gamer', 'Headset gamer com microfone', 149.90, '2030-12-31', 40),


-- categoria de eletronicos 2
('Webcam Full HD', 'Webcam para computador', 199.90, '2030-12-31', 20),

('Caixa de Som', 'Caixa de som USB', 119.90, '2030-12-31', 25),

('Controle USB', 'Controle USB para computador', 129.90, '2030-12-31', 35),


-- categoria de alimento 3
('Arroz 5kg', 'Arroz tipo 1', 24.90, '2027-05-20', 100),

('Feijão 1kg', 'Feijão carioca tipo 1', 8.50, '2027-04-15', 100),

('Macarrão 500g', 'Macarrão espaguete', 5.99, '2027-08-10', 80),

('Açúcar 1kg', 'Açúcar refinado', 4.79, '2027-06-12', 90),

('Café 500g', 'Café torrado e moído', 18.90, '2027-03-20', 70),


-- categoria de limpeza 4
('Detergente 500ml', 'Detergente líquido', 3.50, '2028-01-10', 200),

('Sabão em Pó 1kg', 'Sabão em pó para roupas', 12.90, '2028-02-15', 100),

('Desinfetante 1L', 'Desinfetante para limpeza', 8.99, '2028-03-20', 120);


-- relacionamento dos produtos com as categorias
INSERT INTO Produto_Categoria
(id_produto, id_categoria)
VALUES

-- Gamer
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),

-- Eletrônicos
(6, 2),
(7, 2),
(8, 2),

-- Alimentos
(9, 3),
(10, 3),
(11, 3),
(12, 3),
(13, 3),

-- Limpeza
(14, 4),
(15, 4),
(16, 4);