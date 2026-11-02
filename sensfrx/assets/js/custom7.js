document.addEventListener("DOMContentLoaded", function () {
  const ctxOdo = document.getElementById("odometerChart").getContext("2d");
  const gaugeData = {
    labels: ["Red", "#ED782C", "#ED782C", "#30B34E", "#30B34E", "#B71C1C"],
    datasets: [
      {
        data: [30, 30, 30, 30, 30, 30],
        backgroundColor: [
          "#30B34E",
          "#30B34E",
          "#ED782C",
          "#ED782C",
          "#E02200",
          "#B71C1C",
        ],
        borderWidth: 12,
        circumference: 180,
        rotation: -90,
        cutout: "70%",
        hoverBackgroundColor: [
          "#30B34E",
          "#30B34E",
          "#ED782C",
          "#ED782C",
          "#E02200",
          "#B71C1C",
        ], // Maintain same colors on hover
        hoverBorderColor: "transparent", // No hover border
      },
    ],
  };

  const gaugeOptions = {
    responsive: true,
    layout: {
      padding: {
        bottom: 0,
      },
    },
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    hover: {
      mode: null, // Disable all hover interactions
    },
  };

  let needleValue = Math.min(Math.max(fraudRate, 0), 100);
  new Chart(ctxOdo, {
    type: "doughnut",
    data: gaugeData,
    options: gaugeOptions,
    plugins: [
      {
        // Plugin to draw the needle
        afterDatasetDraw(chartInstance, args, options) {
          const {
            ctx,
            chartArea: { left, right, bottom },
            width,
          } = chartInstance;
          //needleValue = needleValue;
          const angle = Math.PI * (1 + needleValue / 100);
          // console.log('needle',needleValue);
          // console.log('angle',angle);
          const cx = width / 2;
          const cy = bottom - 80; // Lower the y-position of the needle and center circle
          const needleLength = width / 3.2; // Reduce the length of the needle
          const gap = 10; // Reduce the gap

          ctx.save();
          ctx.translate(cx, cy);
          ctx.rotate(angle);
          ctx.beginPath();
          ctx.moveTo(gap, -8);
          ctx.lineTo(needleLength, 0);
          ctx.lineTo(gap, 8);
          ctx.arc(gap, 0, 8, Math.PI / 2, -Math.PI / 2, true);
          ctx.fillStyle = "#323232";
          ctx.fill();
          ctx.restore();

          // Draw center circle with smaller radius
          ctx.beginPath();
          ctx.arc(cx, cy, 12, 0, Math.PI * 2); // Smaller radius
          ctx.fillStyle = "#323232";
          ctx.fill();
        },
      },
    ],
  });
  //   line chart dashboard

  const ctx = document.getElementById("lineChart").getContext("2d");
  new Chart(ctx, {
    type: "line",
    lineJoin: "round",
    data: {
      labels: ["0", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      datasets: [
        {
          label: "Fraud API Usage Over Time",
          data: [1, 150, 100, 250, 120, 230, 100, 240, 100, 300],
          borderColor: "blue",
          borderWidth: 2,
          tension: 0.4, // Smooth curves
          fill: false,
          pointBackgroundColor: "white",
          pointBorderColor: "blue",
          pointBorderWidth: 2,
          pointRadius: 5,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          min: 0,
          grid: {
            display: false, // Hide vertical gridlines
          },
        },
        y: {
          min: 0,
          max: 500,
          ticks: {
            stepSize: 100,
          },
          grid: {
            color: "#e0e0e0", // Light gray for horizontal gridlines
            borderDash: [5, 5], // Dashed gridlines
          },
        },
      },
    },
  });

  //   multiline chart for transaction tab
  const ctxTM = document.getElementById("transmultilineChart").getContext("2d");

  // Data points for the y-axis
  const yData = [
    0, 40, 100, 150, 220, 230, 270, 295, 360, 370, 390, 425, 400, 300, 320, 340,
    410,
  ];
  const yData2 = [
    0, 20, 80, 120, 290, 210, 250, 275, 330, 340, 380, 415, 420, 335, 328, 315,
    405,
  ];
  const yData3 = [0, 40, 70, 90, 100, 150, 200, 220, 250, 290, 350, 470, 410];
  const xLabels = ["0", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

  // Generate x-axis positions to map yData between xLabels
  const xPositions = [
    0, 1, 1.2, 1.5, 2, 2.2, 2.5, 3, 3.4, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7,
  ];
  const dataPoints = yData.map((y, index) => ({ x: xPositions[index], y }));
  const dataPoints2 = yData2.map((y, index) => ({ x: xPositions[index], y }));
  const dataPoints3 = yData3.map((y, index) => ({ x: xPositions[index], y }));

  // Initialize Chart.js
  new Chart(ctxTM, {
    type: "line",
    data: {
      labels: xLabels,
      datasets: [
        {
          data: dataPoints,
          borderColor: "rgb(48 179 78)",
          backgroundColor: "rgb(255 255 255)",
          fill: false,
          tension: 0.1,
          label: "Approved",
          pointStyle: "circle",
          pointBackgroundColor: "rgb(48 179 78)",
        },
        {
          data: dataPoints2,
          borderColor: "rgb(237 120 44)",
          backgroundColor: "rgb(255 255 255)",
          fill: false,
          tension: 0.1,
          label: "Reviewed",
          pointStyle: "circle",
          pointBackgroundColor: "rgb(237 120 44)",
        },
        {
          data: dataPoints3,
          borderColor: "red",
          backgroundColor: "rgb(255 255 255)",
          fill: false,
          tension: 0.1,
          label: "Denied",
          pointStyle: "circle",
          pointBackgroundColor: "red",
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          mode: "nearest",
          intersect: false,
        },
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            display: false,
          },
          type: "linear",
          position: "bottom",
          title: {
            display: true,
            text: "Days",
          },
          ticks: {
            callback: function (value) {
              return xLabels[value] || "";
            },
            stepSize: 1,
          },
        },
        y: {
          beginAtZero: true,
          min: 0,
          max: 500,
          ticks: {
            stepSize: 100,
          },
          title: {
            display: true,
            text: "Transaction Amount",
          },
        },
      },
    },
  });

  //   bar chart transaction

  const canvasB = document.getElementById("transbarChart");
  const ctxb = canvasB.getContext("2d");

  // Create the chart instance
  const barChart = new Chart(ctxb, {
    type: "bar",
    data: {
      labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"], // Days of the week
      borderRadius: 5,
      datasets: [
        {
          label: "Approved",
          data: [100, 200, 150, 250, 190, 210, 250], // Approved data
          backgroundColor: "#30B34E",
          barThickness: 40,
        },
        {
          label: "Reviewed",
          data: [50, 100, 75, 125, 110, 140, 150], // Reviewed data
          backgroundColor: "#ED782C",
          barThickness: 40,
        },
        {
          label: "Denied",
          data: [20, 50, 30, 60, 60, 100, 80], // Denied data
          backgroundColor: "#E02200",
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
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          stacked: true,
          grid: {
            display: false, // Hide vertical gridlines
          },
        },
        y: {
          stacked: true,
          min: 0,
          max: 500,
          ticks: {
            stepSize: 100,
          },
          beginAtZero: true,
          title: {
            display: true,
            text: "Transaction Number",
          },
        },
      },
    },
  });

  // Cleanup the chart instance on component unmount

  //   pie chart transaction
  const canvas = document.getElementById("transpieChart");
  const ctxP = canvas.getContext("2d");

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
  const pieChart = new Chart(ctxP, {
    type: "pie",
    data: data,
    options: options,
  });

  return () => {
    pieChart.destroy();
    tooltipEl.remove();
    barChart.destroy();
  };
});
