document.addEventListener("DOMContentLoaded", function () {
  // Initialize Chart.js after the component has been mounted
  const canvas = document.getElementById("botbarChart");
  const ctxb = canvas.getContext("2d");

  // Create the bar chart instance
  const barChart = new Chart(ctxb, {
    type: "bar",
    data: {
      labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      datasets: [
        {
          label: "Approved",
          data: [100, 200, 80, 250, 190, 210, 250],
          backgroundColor: "#0254FF",
          barThickness: 40,
        },
        {
          label: "Reviewed",
          data: [50, 100, 30, 125, 110, 140, 150],
          backgroundColor: "#ED782C",
          barThickness: 40,
        },
        {
          label: "Denied",
          data: [20, 50, 20, 60, 60, 100, 80],
          backgroundColor: "#ECDD3D",
          barThickness: 40,
          borderRadius: {
            topLeft: 10,
            topRight: 10,
          },
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: {
          stacked: true,
          grid: { display: false },
        },
        y: {
          stacked: true,
          min: 0,
          max: 500,
          ticks: { stepSize: 100 },
          beginAtZero: true,
          title: { display: true, text: "Transaction Number" },
        },
      },
    },
  });

  // Now proceed with the second chart
  const lineCanvas = document.getElementById("lineplainChart");
  const ctxline = lineCanvas.getContext("2d");

  const lineChart = new Chart(ctxline, {
    type: "line",
    data: {
      labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      datasets: [
        {
          label: "Suspicious IPs",
          data: [0, 100, 300, 400, 400, 400, 400],
          borderColor: "green",
          borderWidth: 2,
          fill: false,
          pointRadius: 0,
        },
        {
          label: "Suspicious User Agents",
          data: [0, 50, 200, 450, 450, 450, 450],
          borderColor: "blue",
          borderWidth: 2,
          fill: false,
          pointRadius: 0,
        },
        {
          label: "Repeated Requests",
          data: [0, 30, 100, 150, 200, 250, 300],
          borderColor: "pink",
          borderWidth: 2,
          fill: false,
          pointRadius: 0,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
      },
      scales: {
        x: {
          title: { display: false },
          grid: { display: false },
        },
        y: {
          min: 0,
          max: 500,
          ticks: { stepSize: 100 },
          title: { display: true, text: "Transaction Numbers" },
          beginAtZero: true,
        },
      },
    },
  });

  //   pie chart registraion
  const canvaspi = document.getElementById("botpieChart");
  const ctxpi = canvaspi.getContext("2d");

  // Tooltip setup
  const tooltipEl = document.createElement("div");
  tooltipEl.id = "chartjs-tooltip";
  tooltipEl.style.position = "absolute";
  tooltipEl.style.background = "#fff";
  tooltipEl.style.color = "whi#03060Bte";
  tooltipEl.style.padding = "5px 10px";
  tooltipEl.style.borderRadius = "3px";
  tooltipEl.style.pointerEvents = "none";
  tooltipEl.style.display = "none";
  document.body.appendChild(tooltipEl);

  const data = {
    labels: ["Low", "Medium", "High", "Critical"],
    datasets: [
      {
        data: [15, 20, 30, 35],
        backgroundColor: ["#f6bb95", "#FAD7A0", "#db8d8d", "#97d9a6"],
        hoverBackgroundColor: ["#30B34E", "#ED782C", "#E02200", "#B71C1C"],
        borderWidth: 0,
      },
    ],
  };

  const options = {
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        enabled: false,
        external: function (context) {
          const { tooltip } = context;

          if (!tooltip || tooltip.opacity === 0) {
            tooltipEl.style.display = "none";
            return;
          }

          tooltipEl.innerHTML = `${tooltip.title[0]}: ${tooltip.body[0].lines[0]}%`;

          const canvasPosition = context.chart.canvas.getBoundingClientRect();

          tooltipEl.style.display = "block";
          tooltipEl.style.left =
            canvasPosition.left + window.scrollX + tooltip.caretX + "px";
          tooltipEl.style.top =
            canvasPosition.top + window.scrollY + tooltip.caretY + "px";
        },
      },
    },
    maintainAspectRatio: true,
    animation: {
      animateScale: true,
      animateRotate: true,
    },
  };

  // Custom plugin to move the hovered slice upwards
  const moveSlicePlugin = {
    id: "moveSlicePlugin",
    beforeDatasetDraw: (chart, args, options) => {
      const activeElements = chart.tooltip?.getActiveElements();
      const dataset = chart.data.datasets[0];

      if (activeElements?.length) {
        const { index } = activeElements[0].element;
        const meta = chart.getDatasetMeta(0);
        meta.data.forEach((arc, i) => {
          arc.offset = i === index ? 20 : 0; // Move the hovered slice upwards
        });
      } else {
        chart.getDatasetMeta(0).data.forEach((arc) => {
          arc.offset = 0; // Reset all slices when not hovered
        });
      }
    },
  };

  // Register the custom plugin
  Chart.register(moveSlicePlugin);

  // Initialize Chart.js
  const pieChartb = new Chart(ctxpi, {
    type: "pie",
    data: data,
    options: options,
  });

  // Cleanup both charts when the component unmounts
  return () => {
    pieChartb.destroy();
    barChart.destroy();
    lineChart.destroy();
  };
});
