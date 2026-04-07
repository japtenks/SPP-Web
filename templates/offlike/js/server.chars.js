document.addEventListener("DOMContentLoaded", function () {
  var headers = document.querySelectorAll(".character-table__header .sortable");
  var table = document.querySelector(".character-table");
  var initialRows = table ? Array.from(table.querySelectorAll(".character-table__row")) : [];

  initialRows.forEach(function (row, index) {
    row.dataset.originalIndex = String(index);
  });

  function getCellSortValue(cell, sortKey) {
    if (!cell) {
      return "";
    }

    if (cell.dataset.sortValue) {
      return cell.dataset.sortValue;
    }

    if (sortKey === "level") {
      return parseInt(cell.textContent.trim(), 10) || 0;
    }

    var link = cell.querySelector("a");
    if (link) {
      return link.textContent.trim().toLowerCase();
    }

    var image = cell.querySelector("img");
    if (image && image.getAttribute("title")) {
      return image.getAttribute("title").trim().toLowerCase();
    }

    return cell.textContent.trim().toLowerCase();
  }

  headers.forEach(function (header) {
    header.addEventListener("click", function () {
      var currentTable = header.closest(".character-table");
      if (!currentTable) {
        return;
      }

      var rows = Array.from(currentTable.querySelectorAll(".character-table__row"));
      var index = Array.from(header.parentNode.children).indexOf(header);
      var sortKey = header.dataset.sort || "";
      var state = header.dataset.state || "none";

      if (state === "none") {
        state = "asc";
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

        var ascending = state === "asc";
        var leftCell = leftRow.querySelectorAll(".character-table__cell")[index];
        var rightCell = rightRow.querySelectorAll(".character-table__cell")[index];
        var leftValue = getCellSortValue(leftCell, sortKey);
        var rightValue = getCellSortValue(rightCell, sortKey);
        var comparison = sortKey === "level"
          ? leftValue - rightValue
          : String(leftValue).localeCompare(String(rightValue), undefined, { numeric: true, sensitivity: "base" });

        if (comparison !== 0) {
          return ascending ? comparison : -comparison;
        }

        return (parseInt(leftRow.dataset.originalIndex || "0", 10) || 0)
          - (parseInt(rightRow.dataset.originalIndex || "0", 10) || 0);
      });

      rows.forEach(function (row) {
        currentTable.appendChild(row);
      });
    });
  });
});
