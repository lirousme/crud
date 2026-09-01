CREATE DATABASE IF NOT EXISTS crud_de_cruds
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crud_de_cruds;

CREATE TABLE cruds (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome_do_crud VARCHAR(150) NOT NULL,
  orientacao_colunas TINYINT NOT NULL DEFAULT 0 COMMENT '0: horizontal; 1: vertical',
  CHECK (orientacao_colunas IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE registros_do_crud (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_crud BIGINT UNSIGNED NOT NULL,
  CONSTRAINT fk_registros_crud FOREIGN KEY (id_crud) REFERENCES cruds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE colunas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome_da_coluna VARCHAR(150) NOT NULL,
  tipo TINYINT NOT NULL DEFAULT 0 COMMENT '0: texto; 1: número; 2: select',
  ordem INT NOT NULL,
  CHECK (tipo IN (0, 1, 2))
) ENGINE=InnoDB;

CREATE TABLE cruds_colunas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_crud BIGINT UNSIGNED NOT NULL,
  id_coluna BIGINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_crud_coluna (id_crud, id_coluna),
  CONSTRAINT fk_cruds_colunas_crud FOREIGN KEY (id_crud) REFERENCES cruds(id) ON DELETE CASCADE,
  CONSTRAINT fk_cruds_colunas_coluna FOREIGN KEY (id_coluna) REFERENCES colunas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE opcoes_colunas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_coluna BIGINT UNSIGNED NOT NULL,
  valor_da_opcao VARCHAR(255) NOT NULL,
  tipo TINYINT NOT NULL DEFAULT 0,
  ordem INT NOT NULL,
  CONSTRAINT fk_opcoes_coluna FOREIGN KEY (id_coluna) REFERENCES colunas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE c_zero_valores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_registro BIGINT UNSIGNED NOT NULL,
  id_coluna BIGINT UNSIGNED NOT NULL,
  valor_da_coluna TEXT NOT NULL,
  UNIQUE KEY uq_zero_valor (id_registro, id_coluna),
  CONSTRAINT fk_zero_registro FOREIGN KEY (id_registro) REFERENCES registros_do_crud(id) ON DELETE CASCADE,
  CONSTRAINT fk_zero_coluna FOREIGN KEY (id_coluna) REFERENCES colunas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE c_um_valores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_registro BIGINT UNSIGNED NOT NULL,
  id_coluna BIGINT UNSIGNED NOT NULL,
  valor_da_coluna INT NOT NULL,
  UNIQUE KEY uq_um_valor (id_registro, id_coluna),
  CONSTRAINT fk_um_registro FOREIGN KEY (id_registro) REFERENCES registros_do_crud(id) ON DELETE CASCADE,
  CONSTRAINT fk_um_coluna FOREIGN KEY (id_coluna) REFERENCES colunas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE c_dois_valores (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_registro BIGINT UNSIGNED NOT NULL,
  id_coluna BIGINT UNSIGNED NOT NULL,
  valor_da_coluna BIGINT UNSIGNED NOT NULL COMMENT 'ID da opção selecionada',
  UNIQUE KEY uq_dois_valor (id_registro, id_coluna),
  CONSTRAINT fk_dois_registro FOREIGN KEY (id_registro) REFERENCES registros_do_crud(id) ON DELETE CASCADE,
  CONSTRAINT fk_dois_coluna FOREIGN KEY (id_coluna) REFERENCES colunas(id) ON DELETE CASCADE,
  CONSTRAINT fk_dois_opcao FOREIGN KEY (valor_da_coluna) REFERENCES opcoes_colunas(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
