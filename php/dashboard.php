<?php
  session_start();
  $tipo = $_SESSION['tipo'];
    if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
  }
  else if ($tipo !== 'coordenador') {
    header("Location: error403.php");
    exit();
  }
  require_once 'conexao.php';
  require_once 'dashboard_data.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard de Desempenho de Manutenção</title>
        <link rel="icon" type="image/png" href="../img/senai-logo5.svg">
        <link rel="stylesheet" href="../css/padrao.css">
        <link rel="stylesheet" href="../css/dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
        <link rel="preload" as="font" href="https://fonts.gstatic.com/s/materialsymbolsoutlined/v249/kJF1BvYX7BgnkSrUwT8OhrdQw4oELdPIeeII9v6oDMzByHX9rA6RzaxHMPdY43zj-jCxv3fzvRNU22ZXGJpEpjC_1v-p_4MrImHCIJIZrDCvHOej.woff2" type="font/woff2" crossorigin="anonymous">        
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" integrity="sha512-CQBWl4fJHWbryGE+Pc7UAxWMUMNMWzWxF4SQo9CgkJIN1kx6djDQZjh3Y8SZ1d+6I+1zze6Z7kHXO7q3UyZAWw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9012455024725100"
     crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js" integrity="sha512-XSmbX3mhrD2ix5fXPTRQb2FwK22sRMVQTpBP2ac8hX7Dh/605hA2QDegVWiAvZPiXIxOV0CbkmUjGionDpbCmw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <script>
          (function() {
            const original = EventTarget.prototype.addEventListener;
            EventTarget.prototype.addEventListener = function(type, listener, options) {
              const passiveEvents = ['touchstart', 'touchmove', 'wheel', 'scroll'];
              if (passiveEvents.includes(type)) {
                if (options === false) {
                  options = { passive: false };
                } else if (typeof options === 'object') {
                  options = Object.assign({}, options, { passive: true });
                } else {
                  options = { passive: true };
                }
              }
              return original.call(this, type, listener, options);
            };
          })();
        </script>
        <style>
          .top-bar{
            margin-bottom:0;
          }
          .dashboard-cards, #cards-metricas{
            height:fit-content;
          }
        </style>
    </head>
    <body class="d-flex flex-md-row w-100 h-100">

        <aside class="sidebar">
            <img src="../img/LogoPortalSenai.svg" alt="Portal Senai Manutenções" class="logo-senai">
            <div class="spacer"></div>
            <img src="../img/TechFix.svg" alt="Techfix Facilities" class="logo-techfix">
        </aside>
        <main class="main-content d-flex flex-md-column w-100 h-100">
            <header class="top-bar mt-2 animate__animated animate__fadeInDown">
                <a href="panelTecnico.php" class="botao-voltar" title="Voltar"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="title text-center mb-4">Dashboard de Desempenho de Manutenção</h2>
                <div class="welcome-msg me-3 text-dark fw-bold">
                  <a href="editar_funcionario.php?id=<?= $_SESSION['id'] ?>" style="text-decoration: none; color: #212529;" title="Editar meus dados" class="username">
                      <?= htmlspecialchars($_SESSION['nome']) ?>
                  </a><br>
                    <span style="font-weight: 300; font-size: 0.85rem; color: #6c757d;">
                        <?php echo htmlspecialchars($_SESSION['tipo']); ?>
                    </span>
                </div>
                <div class="logout-btn">
                    <a href="logout.php" title="Sair">
                        <img src="../img/logout.svg" alt="Logout" style="width: 24px; height: 24px;">
                    </a>
                </div>
            </header>
            
            <div class="dashboard-container d-flex flex-columnflex-wrap flex-md-column justify-content-evenly gap-4 mt-3 w-100" style="position:relative;left:0; min-height:100vh;overflow:auto;">                
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start align-top gap-4 w-100" id="topo">
                <div class="d-flex justify-content-end px-4 w-100" id="ano-selector" class="d-flex animate_animated animate__fadeInDownBig">
                <form method="get" id="anoForm" class="form-inline d-flex animate_animated animate__fadeInDownBig" style="width: fit-content; min-width: 200px; flex-direction: row; align-items: center; gap:10px;">
                <a href="insights.php" title="Comparativo entre anos" class="btn btn-outline-secondary" style="border-radius: 50%; padding: 0.5rem; width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined">query_stats</span>
                  </a><label for="ano" class="me-2">Ano: 
                </label>
                  <select name="ano" id="ano" class="form-select w-auto" onchange="document.getElementById('anoForm').submit();">
                    <?php foreach ($anosDisponiveis as $ano): ?>
                      <option value="<?= $ano ?>" <?= $ano == $anoSelecionado ? 'selected' : '' ?>>
                        <?= $ano ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
              <?php if ($anoSelecionado == date('Y')): ?>
                    <div class="card p-3 m-auto shadow-sm w-25 h-75 animate__animated animate__fadeInLeft" style="flex:1; min-width:260px; max-width: 48%; position:relative;left:0;"  id="status-hoje">
                        <h5 class="title"><i class="fa-solid fa-list-ul"></i> Status de Hoje</h5>
                        <div class="box ">
                            <ul class="list-group">
                                <li class="list-group-item "><b>Pendente:</b> <?= $totaisHoje['Pendente'] ?></li>
                                <li class="list-group-item"><b>Em Andamento:</b> <?= $totaisHoje['Em Andamento'] ?></li>
                                <li class="list-group-item"><b>Concluído:</b> <?= $totaisHoje['Concluído'] ?></li>
                                <li class="list-group-item fw-bold"><b>Total:</b> <?= $totalHoje ?></li>
                                <li class="list-group-item text-success"><b>SLA (<= 3 dias): </b><?= $sla ?>%</li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="card p-3 m-auto shadow-sm h-100 d-flex flex-column" style="min-width:500px;" id="tecnicos">
                        <h5 class="title"><i class="fa-solid fa-ticket"></i> Tickets por Atendente</h5>
                        <div class="table-wrapper w-100" style="overflow-x:auto; width:100%;">
                                <table class="w-100" style="border-radius:15px 15px 0 0; ">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th class="status-pendente">Pendente</th>
                                            <th class="status-andamento">Andamento</th>
                                            <th class="status-concluido">Concluído</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($atendentes as $a): ?>
                                        <tr>
                                        <td class="nomeAtendente">
                                                <?php if (isset($a['nif'])): ?>
                                                    <a href="tecnico_detalhes.php?nif=<?= urlencode($a['nif']) ?>">
                                                        <?= htmlspecialchars($a['nome']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($a['nome']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Pendentes" class="status-pendente"><b><?= $a['pendente'] ?></b></td>
                                            <td data-label="Em Andamento" class="status-andamento"><?= $a['andamento'] ?></td>
                                            <td data-label="Concluídos" class="status-concluido"><?= $a['concluido'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <nav class="mt-2">
                                    <ul class="pagination paginacao justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="?pagina_atendentes=<?= $i ?>#tickets-atendentes"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                          </div>
                    </div>
                    <div class="dashboard-cards p-3 m-auto w-50 animate__animated animate__fadeInRight" id="cards-metricas" style="min-width: 200px; flex:1;">

                            <div class="info-box bg-pink hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    pending_actions
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Pendentes</div>
                                    <div class="number count-to" data-from="0" data-to="125" data-speed="15" data-fresh-interval="20"><?= $pendentes ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-orange hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    content_paste
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Em Andamento</div>
                                    <div class="number count-to" data-from="0" data-to="1225" data-speed="1000" data-fresh-interval="20"><?= $emAndamento ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-light-green hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    assignment_turned_in
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Finalizados</div>
                                    <div class="number count-to" data-from="0" data-to="243" data-speed="1000" data-fresh-interval="20"><?= $finalizados ?></div>
                                </div>
                            </div>
                            <div class="info-box bg-cyan hover-expand-effect">
                                <div class="icon">
                                    <span class="material-symbols-outlined">
                                    hourglass_bottom
                                    </span>
                                </div>
                                <div class="content">
                                    <div class="text">Tempo Médio de Conclusão</div>
                                    <div class="number count-to" data-from="0" data-to="257" data-speed="1000" data-fresh-interval="20"><?= $mediaDias ?> dias</div>
                                </div>
                            </div>

                      </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="min-width:520px; min-height: 420px;">
                            <h5 class="title text-center"><i class="fa-solid fa-chart-pie"></i> Distribuição por Status</h5>
                            <div id="statusChart" style="width: 100%; height: 400px;"></div>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm" style="width: 700px;">

                            <!---<canvas id="multiLineChart" height="320px" ></canvas>-->
                            <div id="graficoArea"></div>
                        </div>
                    </div>

                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap" >
                    <!-- Gráfico de Pizza (à esquerda) -->
                    <div class="p-2 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width:200px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm" style="width: fit-content; min-width:300px;">
                        <div id="graficoSetor" style="width: 100%; min-height:280px; min-width:500px;"></div>
                        </div>
                    </div>
                    <div class="p-2 m-auto animate__animated animate__fadeInUp" style="min-width: 500px; flex:1;">
                        <div class="chart-container card p-3 shadow-sm w-100">
                            <div class="chart-wrapper">
                                <canvas id="backlogChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap">
                <div class="p-2 m-auto animate__animated animate__fadeInUp"  style="flex:1; min-width: 150px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm" style="width: fit-content; min-height: 400px;">
                            <div class="chart-wrapper"  style="min-width:320px; min-height:350px; overflow-x: auto;">
                                <canvas id="rankingChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width: 450px; min-height: 300px;">
                            <div class="chart-container card p-3 shadow-sm" style="width: fit-content;">
                                <div class="chart-wrapper w-100">
                                    <canvas id="chartSLA" style="width:100%;"></canvas>
                                </div>
                            </div>
                      </div>
                </div>
                <div class="dashboard-group d-flex flex-row justify-content-evenly align-items-start gap-4 w-100 flex-wrap"  style="flex:1; min-width: 300px;">
                    <div class="p-2  m-auto animate__animated animate__fadeInUp">
                        <div class="chart-container card p-3 shadow-sm">
                            <div class="chart-wrapper">
                            <div id="chartCross" style="width: 500px; height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2  w-75 h-100 m-auto animate__animated animate__fadeInUp" style="flex:1; min-width: 450px; min-height: 300px;">
                        <div class="chart-container card p-3 shadow-sm">
                        <select id="prioridadeFiltro" class="form-select w-auto mb-2">
                          <option value="Todas">Todas as Prioridades</option>
                          <option value="Urgente">Urgente</option>
                          <option value="Média">Média</option>
                          <option value="Baixa">Baixa</option>
                        </select>
                            <div class="chart-wrapper" height="100%">
                                <canvas id="chartYoY" width="85%" ></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            </div>
            </div>
            </div>
            <?php if ($_SESSION['tipo'] == 'coordenador'): ?>
            <a href="listar_funcionarios.php" class="" title="Visualizar funcionários">
                <img src="../img/gerenciar-grupo.png" alt="add-user" class="add-user-img animate__animated animate__fadeInUp">
            </a>
            <a href="export_data.php" class="" title="Exportar dados">
            <img src="../img/exportar-arquivo.png" alt="add-user" class="add-dash-img animate__animated animate__fadeInUp">
            </a>
            <a href="solicita.php" class="" title="Abrir ordem de serviço">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>
    <?php else :?>
            <a href="solicita.php" class="">
        <img src="../img/documento.png" alt="Adicionar" class="add-button-img animate__animated animate__fadeInUp">
    </a>

  <?php endif; ?>
  <script>
    // captura as cores diretamente das info-box
    const infoPink       = getComputedStyle(document.querySelector('.bg-pink')).backgroundColor;
    const infoOrange     = getComputedStyle(document.querySelector('.bg-orange')).backgroundColor;
    const infoLightGreen = getComputedStyle(document.querySelector('.bg-light-green')).backgroundColor;
    const infoCyan       = getComputedStyle(document.querySelector('.bg-cyan')).backgroundColor;
    const cores = [
      '#000000', // Preto
      '#e6b5aa', // Rosa claro
      '#004080', // Azul escuro
      '#007080', // Ciano escuro
      '#30a0a0', // Ciano médio
      '#38a850', // Verde claro
      '#70b080', // Verde médio
      '#a0b870', // Verde oliva
      '#f0e850', // Amarelo claro
      '#f8a800', // Laranja
      '#b01030', // Vermelho escuro
      '#8c003c'  // Magenta escuro
    ];
    // array de cores para statusChart
    const infoColors = [
      infoPink,       // Pendente
      infoOrange,     // Em andamento
      infoLightGreen, // Concluído
      infoCyan        // Rejeitado (você pode realocar se quiser outro mapeamento)
    ];
          // Dados gerados pelo PHP
          const labelsAge = <?= json_encode($labelsAge ?? []) ?>;
          const dataAge = <?= json_encode($dataAge ?? []) ?>;
      const prioridades   = <?= json_encode($prioridades ?? []) ?>;
      const tempoMedio    = <?= json_encode($tempoMedioPorPrioridade ?? []) ?>;
      const totalChamados = <?= json_encode($totalChamadosPorPrioridade ?? []) ?>;
      // Ano atualmente selecionado (usado em todas as chamadas AJAX)
      const yearSelected = <?= json_encode($anoSelecionado) ?>;
    // -----------------------------
    // 1) Instância os charts em variáveis (exceto mesChart)
    // -----------------------------
    const rankingCtx  = document.getElementById('rankingChart').getContext('2d');

    const statusChart = echarts.init(document.getElementById('statusChart'), null, {
    devicePixelRatio: 2
    });

    const rankingChart = new Chart(rankingCtx, {
      type: 'polarArea',
      data: {
        labels: <?= json_encode($responsaveis) ?>,
        datasets: [{
          label: 'Chamados por Responsável',
          data: <?= json_encode($totais) ?>,
          backgroundColor: <?= json_encode($cores) ?>
        }]
      },
      options: {
        animation: false,
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: {
            display: true,
            text: 'Chamados por Responsável'
          }
        }
      }
    });


    const optionStatusChart = {
      legend: {
      type: 'scroll',           // ativa rolagem se necessário
      orient: 'horizontal',     // horizontal (embaixo do gráfico)
      bottom: 10,               // espaçamento do fundo
      align: 'auto',            // alinha automaticamente
      textStyle: {
        fontSize: 12
      },
      pageIconSize: 12          // tamanho dos ícones de página se for scrollável
    },
      toolbox: {
        show: true,
        feature: {
          mark: { show: true },
          dataView: { show: true, readOnly: false },
          restore: { show: true },
          saveAsImage: { show: true }
        }
      },
      tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)'
      },
      series: [{
      name: 'Distribuição por Status',
      type: 'pie',
      roseType: 'area',
      radius: [40, 150],
      center: ['50%', '45%'],
      itemStyle: {
        borderRadius: 8
      },
      label: {
        show: true,
        formatter: '{b}\n{c} ({d}%)'
      },
      data: [
        { value: <?= $pendentes ?>, name: 'Pendente', itemStyle: { color: '#ec1961' } },
        { value: <?= $emAndamento ?>, name: 'Em Andamento', itemStyle: { color: '#ff9800' } },
        { value: <?= $finalizados ?>, name: 'Concluído', itemStyle: { color: '#8bc34a' } },
        { value: <?= $rejeitados ?>, name: 'Rejeitado', itemStyle: { color: '#00bcd4' } } // ou ajuste conforme sua paleta
      ]
    }]


    };

    statusChart.setOption(optionStatusChart);
      // 2) Gráfico de idade
      // Instanciando o gráfico com ECharts
      const backlogOption = {
          title: {
            text: 'Tickets Pendentes por Idade',
            left: 'center',
            top: 'top',
            textStyle: {
              fontSize: 10
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' }
          },
          grid: {
            left: '10%',
            right: '10%',
            bottom: '10%',
            top: '20%',
            containLabel: true
          },
          xAxis: {
            type: 'value',
            min: 0
          },
          yAxis: {
            type: 'category',
            data: <?= json_encode($labelsAge) ?>,
            axisLabel: {
              rotate: 0 // sem rotação agora
            }
          },
          series: [{
            name: 'Tickets Pendentes por Idade',
            type: 'bar',
            data: <?= json_encode($dataAge) ?>,
            itemStyle: {
              color: '#3f51b5'
            },
            barWidth: '50%'
          }]
        };

        const backlogChart = echarts.init(
          document.getElementById('backlogChart'),
          null,
          { devicePixelRatio: 2 }
        );
        backlogChart.setOption(backlogOption);

        // 1) SLA Compliance por Prioridade
        const chartSLA = new Chart(
          document.getElementById('chartSLA').getContext('2d'),
          {
            type: 'bar',
            data: {
              labels: <?= json_encode($prioridades) ?>,
              datasets: [{
                label: '% Compliance SLA',
                data: <?= json_encode($slaPorPrioridade) ?>,
              }]
            },
            options: {
              plugins: {
                title: {
                  display: true,
                  text: 'SLA Compliance por Prioridade'
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  stacked: false,
                  title: {
                    display: true,
                    text: 'Total de Pedidos'
                  }
                },
                x: {
                  title: {
                    display: true,
                    text: 'Mês'
                  }
                }
              }

            }
          }
        );

        let estadoAtual = {
      ano: new Date().getFullYear(),
      mes: null
    };

    function carregarGrafico(ano, mes = null) {
      const url = mes ? `grafico_area.php?ano=${ano}&mes=${mes}` : `grafico_area.php?ano=${ano}`;
      
      fetch(url)
        .then(res => res.json())
        .then(({ labels, dados }) => {
          const cores = {
            'Baixa - Pendente': '#e91e63',
            'Baixa - Em Andamento': '#ff9800',
            'Baixa - Concluído': '#8bc34a',
            'Média - Pendente': '#9c27b0',
            'Média - Em Andamento': '#ffc107',
            'Média - Concluído': '#4caf50',
            'Alta - Pendente': '#03a9f4',
            'Alta - Em Andamento': '#ff5722',
            'Alta - Concluído': '#009688',
            'Urgente - Pendente': '#f44336',
            'Urgente - Em Andamento': '#ffeb3b',
            'Urgente - Concluído': '#607d8b'
          };

          const series = [];
          for (const chave in dados) {
            series.push({
              name: chave,
              type: 'line',
              stack: 'total',
              areaStyle: {},
              emphasis: { focus: 'series' },
              data: labels.map(p => dados[chave][p] || 0),
              color: cores[chave] || undefined
            });
          }

          const chart = echarts.init(document.getElementById('graficoArea'));
          const titulo = mes 
            ? `Detalhes do mês ${estadoAtual.ano}-${String(mes).padStart(2, '0')}`
            : `Visão geral de ${estadoAtual.ano}`;

          chart.setOption({
            title: { text: titulo, left: 'center' ,  textStyle: {
        fontSize: 16
      }},
            tooltip: { trigger: 'axis' },
            legend: {
              orient: 'vertical',
              right: 10,
              top: 'middle',
              type: 'scroll',
              textStyle: {
                fontSize: 12
              }
            },
            toolbox: {
              feature: {
                saveAsImage: {},
                myDrillUp: {
                  show: !!mes,
                  title: 'Voltar',
                  icon: 'path://M512 64L96 320l416 256V64z',
                  onclick: function () {
                    estadoAtual.mes = null;
                    carregarGrafico(estadoAtual.ano);
                  }
                }
              }
            },
            grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
            xAxis: { type: 'category', boundaryGap: false, data: labels },
            yAxis: { type: 'value' },
            series: series
          });

          chart.off('click');
          chart.on('click', function (params) {
            if (!estadoAtual.mes && params.name.includes('-')) {
              const partes = params.name.split('-');
              const mesClicado = parseInt(partes[1]);
              estadoAtual.mes = mesClicado;
              carregarGrafico(estadoAtual.ano, mesClicado);
            }
          });
        })
        .catch(error => {
          console.error('Erro ao carregar gráfico:', error);
        });
    }

    carregarGrafico(estadoAtual.ano);

    
        const chartYoY = new Chart(document.getElementById('chartYoY'), {
      type: 'line',
      data: {
        labels: <?= json_encode(['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']) ?>,
        datasets: <?= json_encode($estilos) ?>
      },
      options: {
        animation: false,
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Comparativo Ano a Ano por Prioridade e Status'
          },
          tooltip: {
            mode: 'index',
            intersect: false
          },
          legend: {
            position: 'bottom'
          }
        },
        interaction: {
          mode: 'index',
          intersect: false
        },
        scales: {
      y: { beginAtZero: true, stacked: true },
      x: { stacked: true }
    }
      }
    });


      const chartCross = echarts.init(
        document.getElementById('chartCross'),
        null,
        { devicePixelRatio: 2 }
    );

    const optionRadarCross = {
      title: {
        text: 'Resolução (dias) × Volume de Chamados',
        left: 'center',
        textStyle: { fontSize: 16 }
      },
      legend: {
        top: 'bottom',
        textStyle: { fontSize: 12 }
      },
      tooltip: {
        trigger: 'item',
        formatter: function (params) {
          const valores = params.value;
          const prioridadeLabels = <?= json_encode($prioridades) ?>;
          let detalhes = valores.map((v, i) => `${prioridadeLabels[i]}: ${v}`).join('<br>');
          return `<b>${params.seriesName} - ${params.name}</b><br>${detalhes}`;
        }
      },
      radar: {
        indicator: prioridades.map((p, i) => ({
          name: p,
          max: Math.max(tempoMedio[i], totalChamados[i]) + 10
        }))
      },
      series: [
        {
          name: 'Comparação',
          type: 'radar',
          data: [
            {
              value: tempoMedio,
              name: 'Tempo Médio (dias)',
              lineStyle: { color: '#007bff' },
              areaStyle: { color: 'rgba(0,123,255,0.2)' }
            },
            {
              value: totalChamados,
              name: 'Total de Chamados',
              lineStyle: { color: '#28a745' },
              areaStyle: { color: 'rgba(40,167,69,0.2)' }
            }
          ]
        }
      ]
    };

    chartCross.setOption(optionRadarCross);

    const fetchSetor = fetch(`dados_por_setor.php?ano=${yearSelected}`)
      .then(res => res.json())
      .then(data => {
        const labels = data.map(item => item.bloco);
        const valores = data.map(item => item.total);
        if (!Array.isArray(valores))  {
          console.error("Erro: valores não é um array", valores);
          return;
        }
        const total = valores.reduce((a, b) => a + b, 0);

        const chartSetor = echarts.init(document.getElementById('graficoSetor'));

        chartSetor.setOption({
      title: {
        text: 'Ordens de Serviço por Bloco',
        left: 'center',
        top: 5, // Reduz o espaço superior
        textStyle: { fontSize: 16 }
      },
      tooltip: {
        trigger: 'item',
        formatter: '{b}: {c} ({d}%)'
      },
      legend: {
        bottom: 10,
        orient: 'horizontal',
        type: 'scroll',
        textStyle: { fontSize: 12 }
      },
      series: [{
        name: 'OS por Bloco',
        type: 'pie',
        radius: ['70%', '95%'],          // Tamanho interno e externo do arco
        center: ['50%', '65%'],          // Posição mais próxima da legenda
        startAngle: 180,
        endAngle: 0,
        avoidLabelOverlap: true,
        itemStyle: { borderRadius: 8 },
        label: { show: false },
        labelLine: { show: false },
        data: labels.map((bloco, i) => ({
          value: valores[i],
          name: bloco
        }))
      }]
    });

      });

      function aplicarFiltroPorStatus(status) {
      // Atualiza título do gráfico YoY
      chartYoY.options.plugins.title.text = `Comparativo Ano a Ano - Status: ${status}`;
      chartYoY.data.datasets = <?= json_encode($estilos) ?>.filter(ds => ds.label.includes(status));
      chartYoY.update();

      // Atualiza gráfico radar CrossTrend (tempo médio e volume por prioridade e status)
      const filtroCross = <?= json_encode($dadosYoYDetalhado) ?>;
      const tempo = [];
      const volume = [];

      <?= json_encode($prioridades) ?>.forEach(p => {
        const valores = filtroCross[yearSelected][status]?.[p] ?? Array(12).fill(0);
        tempo.push((valores.reduce((a,b)=>a+b,0) / 12).toFixed(1));
        volume.push(valores.reduce((a,b)=>a+b,0));
      });

      chartCross.setOption({
        title: { text: `Resolução × Volume (${status})` },
        series: [{
          data: [
            { value: tempo, name: 'Tempo Médio' },
            { value: volume, name: 'Total Chamados' }
          ]
        }]
      });

      // Recarrega gráfico de área por prioridade e status
      carregarGrafico(yearSelected); // mantém estadoAtual.mes se desejar

      // Exemplo: filtro no gráfico por setor (se quiser implementar)
      fetch(`dados_por_setor.php?ano=${yearSelected}&status=${encodeURIComponent(status)}`)
        .then(res => res.json())
        .then(data => {
          const labels = data.map(item => item.bloco);
          const valores = data.map(item => item.total);
          const chartSetor = echarts.init(document.getElementById('graficoSetor'));

          chartSetor.setOption({
            title: { text: `Ordens por Bloco (${status})` },
            series: [{
              data: labels.map((bloco, i) => ({
                value: valores[i],
                name: bloco
              }))
            }]
          });
        });
    }

      statusChart.on('click', function (params) {
      const statusSelecionado = params.name;
      aplicarFiltroPorStatus(statusSelecionado);
    });
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        </main>

    </body>
</html>