document.addEventListener("DOMContentLoaded", function () {
  var table = document.querySelector(".honor-table");
  if (!table) {
    return;
  }

  var headers = Array.from(table.querySelectorAll(".honor-table__header .sortable"));
  var initialRows = Array.from(table.querySelectorAll(".honor-table__row"));
  var tooltip = document.getElementById("honorRankTooltip");
  var titleNode = tooltip ? tooltip.querySelector(".honor-rank-tooltip__title") : null;
  var copyNode = tooltip ? tooltip.querySelector(".honor-rank-tooltip__copy") : null;

  initialRows.forEach(function (row, index) {
    row.dataset.originalIndex = String(index);
  });

  function getCellValue(row, index, isNumeric) {
    var cell = row.querySelectorAll(".honor-table__cell")[index];
    if (!cell) {
      return isNumeric ? 0 : "";
    }

    var explicitValue = cell.dataset.sortValue || "";
    if (isNumeric) {
      return parseInt(explicitValue || cell.textContent.trim(), 10) || 0;
    }

    return String(explicitValue || cell.textContent || "").trim().toLowerCase();
  }

  headers.forEach(function (header) {
    header.addEventListener("click", function () {
      var index = headers.indexOf(header);
      var sortKey = header.dataset.sort || "";
      var state = header.dataset.state || "none";
      var rows = Array.from(table.querySelectorAll(".honor-table__row"));
      var isNumeric = sortKey === "level" || sortKey === "hk" || sortKey === "dk" || sortKey === "rank" || sortKey === "honor";

      if (state === "none") {
        state = sortKey === "honor" ? "desc" : "asc";
      } else if (state === "asc") {
        state = "desc";
      } else {
        state = "none";
      }

      headers.forEach(function (otherHeader) {
        otherHeader.dataset.state = otherHeader === header ? state : "none";
      });

      rows.sort(function (leftRow, rightRow) {
        if (state === "none") {
          return (parseInt(leftRow.dataset.originalIndex || "0", 10) || 0)
            - (parseInt(rightRow.dataset.originalIndex || "0", 10) || 0);
        }

        var leftValue = getCellValue(leftRow, index, isNumeric);
        var rightValue = getCellValue(rightRow, index, isNumeric);
        var comparison = isNumeric
          ? leftValue - rightValue
          : String(leftValue).localeCompare(String(rightValue), undefined, { numeric: true, sensitivity: "base" });

        if (comparison !== 0) {
          return state === "asc" ? comparison : -comparison;
        }

        return (parseInt(leftRow.dataset.originalIndex || "0", 10) || 0)
          - (parseInt(rightRow.dataset.originalIndex || "0", 10) || 0);
      });

      rows.forEach(function (row) {
        table.appendChild(row);
      });
    });
  });

  if (!tooltip || !titleNode || !copyNode) {
    return;
  }

  function moveTooltip(event) {
    var offset = 18;
    var left = event.clientX + offset;
    var top = event.clientY + offset;
    var rect = tooltip.getBoundingClientRect();

    if (left + rect.width > window.innerWidth - 12) {
      left = event.clientX - rect.width - offset;
    }

    if (top + rect.height > window.innerHeight - 12) {
      top = event.clientY - rect.height - offset;
    }

    tooltip.style.left = left + "px";
    tooltip.style.top = top + "px";
  }

  function showTooltip(event, rankNode) {
    var title = rankNode.dataset.rankTitle || "";
    var copy = rankNode.dataset.rankCopy || "";
    if (!title || !copy) {
      return;
    }

    titleNode.textContent = title;
    copyNode.textContent = copy;
    tooltip.classList.add("is-visible");
    tooltip.setAttribute("aria-hidden", "false");
    moveTooltip(event);
  }

  function hideTooltip() {
    tooltip.classList.remove("is-visible");
    tooltip.setAttribute("aria-hidden", "true");
  }

  table.querySelectorAll(".honor-rank-wrap[data-rank-copy]").forEach(function (rankNode) {
    rankNode.addEventListener("mouseenter", function (event) {
      showTooltip(event, rankNode);
    });
    rankNode.addEventListener("mousemove", moveTooltip);
    rankNode.addEventListener("mouseleave", hideTooltip);
    rankNode.addEventListener("focus", function () {
      var rect = rankNode.getBoundingClientRect();
      showTooltip({
        clientX: rect.right,
        clientY: rect.top
      }, rankNode);
    });
    rankNode.addEventListener("blur", hideTooltip);
  });
});
