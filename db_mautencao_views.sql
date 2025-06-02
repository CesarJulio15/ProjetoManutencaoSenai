CREATE OR REPLACE VIEW vw_pedidos_por_status AS
SELECT status, COUNT(*) AS total
FROM manutencao
GROUP BY status;
CREATE OR REPLACE VIEW vw_pedidos_mes_status AS
SELECT 
  DATE_FORMAT(data_abertura, '%Y-%m') AS ano_mes,
  status,
  COUNT(*) AS total
FROM manutencao
GROUP BY ano_mes, status
ORDER BY ano_mes DESC;
CREATE OR REPLACE VIEW vw_ranking_executantes AS
SELECT 
  executante,
  COUNT(*) AS total
FROM manutencao
WHERE executante IS NOT NULL
GROUP BY executante
ORDER BY total DESC;
CREATE OR REPLACE VIEW vw_pedidos_por_status AS
SELECT status, COUNT(*) AS total
FROM manutencao
GROUP BY status;
CREATE OR REPLACE VIEW vw_pedidos_mes_status AS
SELECT 
  DATE_FORMAT(data_abertura, '%Y-%m') AS ano_mes,
  status,
  COUNT(*) AS total
FROM manutencao
GROUP BY ano_mes, status
ORDER BY ano_mes DESC;
CREATE OR REPLACE VIEW vw_ranking_executantes AS
SELECT 
  executante,
  COUNT(*) AS total
FROM manutencao
WHERE executante IS NOT NULL
GROUP BY executante
ORDER BY total DESC;
CREATE OR REPLACE VIEW vw_pedidos_por_bloco AS
SELECT 
  bloco,
  COUNT(*) AS total
FROM manutencao
GROUP BY bloco
ORDER BY total DESC;
CREATE OR REPLACE VIEW vw_sla_medio_por_bloco AS
SELECT 
  bloco,
  AVG(DATEDIFF(data_encerramento, data_abertura)) AS sla_medio_dias
FROM manutencao
WHERE status = 'Concluído'
GROUP BY bloco;

CREATE OR REPLACE VIEW vw_ano_semana_status_prioridade AS
SELECT 
    YEAR(data_abertura) AS ano,
    WEEK(data_abertura, 1) AS semana,
    status,
    prioridade,
    COUNT(*) AS total
FROM manutencao
GROUP BY ano, semana, status, prioridade;