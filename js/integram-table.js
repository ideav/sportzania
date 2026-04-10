/**
 * integram-table.js
 *
 * Компонент таблицы Integram с поддержкой горизонтальной прокрутки,
 * счётчика строк и вставки данных.
 *
 * Использование:
 *   const table = new IntegramTable('#container', { columns: [...], data: [...] });
 */

(function (global) {
    'use strict';

    /**
     * Стили компонента.
     * Встраиваются в <head> при первом создании экземпляра.
     */
    const STYLES = `
.integram-table-wrapper {
    position: relative;
    overflow: hidden;
}

/* ─── Заголовки колонок ─────────────────────────────────────────────── */
.column-header-content {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── Счётчик прокрутки ─────────────────────────────────────────────── */
/* Размер шрифта совпадает с .column-header-content */
.scroll-counter {
    font-size: 0.75rem;
    font-weight: 400;
    color: #6c757d;
    user-select: none;
    white-space: nowrap;
}

/* ─── Панель инструментов ───────────────────────────────────────────── */
.integram-table-toolbar {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    flex-wrap: wrap;
}

/* ─── Кнопки — компактный размер (btn-sm) ───────────────────────────── */
.integram-table-toolbar .btn {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    line-height: 1.4;
    border-radius: 0.2rem;
}

/* ─── Кнопка «Вставить данные» — вторичный контурный стиль ─────────── */
.paste-data-btn {
    color: #6c757d;
    background-color: transparent;
    border: 1px solid #6c757d;
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
    line-height: 1.4;
    border-radius: 0.2rem;
    cursor: pointer;
    transition: color 0.15s ease-in-out,
                background-color 0.15s ease-in-out,
                border-color 0.15s ease-in-out;
}

.paste-data-btn:hover,
.paste-data-btn:focus {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
    outline: none;
}

.paste-data-btn:active {
    color: #fff;
    background-color: #545b62;
    border-color: #4e555b;
}

/* ─── Таблица ───────────────────────────────────────────────────────── */
.integram-table-scroll {
    overflow-x: auto;
}

.integram-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.integram-table th,
.integram-table td {
    padding: 0.3rem 0.5rem;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

.integram-table thead th {
    background: #f1f3f5;
    position: sticky;
    top: 0;
    z-index: 1;
}
`;

    let stylesInjected = false;

    function injectStyles() {
        if (stylesInjected) return;
        const style = document.createElement('style');
        style.type = 'text/css';
        style.textContent = STYLES;
        document.head.appendChild(style);
        stylesInjected = true;
    }

    /**
     * @param {string|HTMLElement} container  CSS-селектор или DOM-элемент
     * @param {Object}             options
     * @param {Array}              options.columns  [{ key, label }]
     * @param {Array}              options.data     [{ key: value }]
     * @param {Function}           [options.onPaste]  Вызывается при нажатии «Вставить данные»
     */
    function IntegramTable(container, options) {
        this._container =
            typeof container === 'string'
                ? document.querySelector(container)
                : container;

        if (!this._container) {
            throw new Error('IntegramTable: контейнер не найден: ' + container);
        }

        this._options = Object.assign({ columns: [], data: [], onPaste: null }, options);
        injectStyles();
        this._render();
    }

    IntegramTable.prototype._render = function () {
        const { columns, data, onPaste } = this._options;

        /* ── Toolbar ───────────────────────────────────────────────── */
        const toolbar = document.createElement('div');
        toolbar.className = 'integram-table-toolbar';

        /* Счётчик строк */
        const counter = document.createElement('span');
        counter.className = 'scroll-counter';
        counter.textContent = 'Строк: ' + data.length;
        toolbar.appendChild(counter);

        /* Кнопка «Вставить данные» */
        const pasteBtn = document.createElement('button');
        pasteBtn.type = 'button';
        pasteBtn.className = 'paste-data-btn';
        pasteBtn.textContent = 'Вставить данные';
        pasteBtn.addEventListener('click', function () {
            if (typeof onPaste === 'function') {
                onPaste();
            }
        });
        toolbar.appendChild(pasteBtn);

        /* ── Таблица ───────────────────────────────────────────────── */
        const scrollWrap = document.createElement('div');
        scrollWrap.className = 'integram-table-scroll';

        const table = document.createElement('table');
        table.className = 'integram-table';

        /* Заголовок */
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        columns.forEach(function (col) {
            const th = document.createElement('th');
            const span = document.createElement('span');
            span.className = 'column-header-content';
            span.textContent = col.label || col.key;
            th.appendChild(span);
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        /* Тело */
        const tbody = document.createElement('tbody');
        data.forEach(function (row) {
            const tr = document.createElement('tr');
            columns.forEach(function (col) {
                const td = document.createElement('td');
                td.textContent = row[col.key] != null ? row[col.key] : '';
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        scrollWrap.appendChild(table);

        /* ── Сборка ────────────────────────────────────────────────── */
        const wrapper = document.createElement('div');
        wrapper.className = 'integram-table-wrapper';
        wrapper.appendChild(toolbar);
        wrapper.appendChild(scrollWrap);

        this._container.innerHTML = '';
        this._container.appendChild(wrapper);

        this._counter = counter;
        this._tbody = tbody;
        this._columns = columns;
    };

    /**
     * Обновить данные таблицы без полного перерендера.
     * @param {Array} newData
     */
    IntegramTable.prototype.setData = function (newData) {
        this._options.data = newData;
        const columns = this._columns;

        this._tbody.innerHTML = '';
        newData.forEach(function (row) {
            const tr = document.createElement('tr');
            columns.forEach(function (col) {
                const td = document.createElement('td');
                td.textContent = row[col.key] != null ? row[col.key] : '';
                tr.appendChild(td);
            });
            this._tbody.appendChild(tr);
        }, this);

        this._counter.textContent = 'Строк: ' + newData.length;
    };

    /* ── Экспорт ───────────────────────────────────────────────────── */
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = IntegramTable;
    } else {
        global.IntegramTable = IntegramTable;
    }

}(typeof globalThis !== 'undefined' ? globalThis : this));
