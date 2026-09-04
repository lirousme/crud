-- Execute uma única vez em bancos criados antes deste recurso.
ALTER TABLE colunas
  ADD COLUMN aceita_valor_igual TINYINT NOT NULL DEFAULT 1
    COMMENT '1: permite valores repetidos; 0: exige valor único'
    AFTER ordem,
  ADD CONSTRAINT chk_colunas_aceita_valor_igual
    CHECK (aceita_valor_igual IN (0, 1));
