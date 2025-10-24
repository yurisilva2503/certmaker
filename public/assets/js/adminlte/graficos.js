function renderGraficoPosts(tema, grafico, elementoGrafico, dadosPostsGrafico) {
  const legendLabelColor = tema === "dark" ? "#ddd" : "#373d3f";
  const axisLabelColor = tema === "dark" ? "#ccc" : "#373d3f";

  // Extrair os arrays para a categoria (datas) e série (quantidade posts)
  const categorias = dadosPostsGrafico.map((item) => item.data_mes);
  const dados = dadosPostsGrafico.map((item) => item.total_posts);

  const sales_chart_options = {
    chart: {
      type: "area",
      height: 300,
      toolbar: { show: false },
      foreColor: axisLabelColor,
      defaultLocale: "br",
    },
    series: [
      {
        name: "Qtd. Posts",
        data: dados, // só números aqui
      },
    ],
    tooltip: {
      theme: tema,
    },
    legend: {
      labels: {
        colors: legendLabelColor,
      },
    },
    xaxis: {
      type: "datetime",
      categories: categorias, // datas aqui
      labels: {
        style: {
          colors: axisLabelColor,
          fontWeight: "400",
          fontFamily: "monospace",
        },
      },
    },
    stroke: {
      curve: "straight",
    },
    dataLabels: {
      enabled: false,
    },
    colors: ["#0d6efd", "#20c997"],
  };

  if (grafico) {
    grafico.destroy();
  }

  grafico = new ApexCharts(elementoGrafico, sales_chart_options);
  grafico.render();

  return grafico;
}
