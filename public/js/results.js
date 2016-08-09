window.onload = function () {
    $('.table').hide();
    $('.header').hide();
    $('#download').hide();
    $('#download_raw').hide();
    $('#to_chart').hide();
    $('#to_main').hide();
    $('#circleG').show();
    var rulesAmount = [],
        permissiveness = [],
        level = [];
    //get the data for chart drawing
    $.ajax({
        url: "/chart",
        dataType: 'json',
        type: "POST",
        success: function (result) {
            $.each(result, function (key, value) {
                rulesAmount.push(value.rules);
                permissiveness.push(Math.round(value.permissiveness));
                level.push(value.level);
            });
        },
        complete: function () {
            $('#circleG').hide();
            $('#to_main').show();
            //draw the chart. Used raphael.js example
            Raphael.fn.drawGrid = function (x, y, w, h, wv, hv, color) {
                color = color || "#000";
                var path = ["M", Math.round(x) + .5, Math.round(y) + .5, "L", Math.round(x + w) + .5, Math.round(y) + .5, Math.round(x + w) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y + h) + .5, Math.round(x) + .5, Math.round(y) + .5],
                //Grid for the chart. Uncomment to view the grid
                    rowHeight = 2 * h / hv,
                    columnWidth = w / wv;


                ////horizontal lines of grid

                //for (var i = 1; i < hv / 2; i++) {
                //    path = path.concat(["M", Math.round(x) + .5, Math.round(y + i * rowHeight) + .5, "H", Math.round(x + w) + .5]);
                //}

                ////vertical lines of grid

                //for (i = 1; i < wv; i++) {
                //    path = path.concat(["M", Math.round(x + i * columnWidth) + .5, Math.round(y) + .5, "V", Math.round(y + h) + .5]);
                //}
                return this.path(path.join(",")).attr({stroke: color});
            };
            // Draw the chart
            var width = 800,
                height = 250,
                leftgutter = 30,
                bottomgutter = 20,
                topgutter = 20,
                colorhue = .6 || Math.random(),
                color = "hsl(" + [colorhue, .5, .5] + ")",
                r = Raphael("holder", width, height),
                txt = {font: '12px Helvetica, Arial', fill: "#fff"},
                X = (width - leftgutter) / rulesAmount.length,
                max = Math.max.apply(Math, permissiveness),
                Y = (height - bottomgutter - topgutter) / max;
            r.drawGrid(leftgutter + X * .5 + .5, topgutter + .5, width - leftgutter - X, height - topgutter - bottomgutter, 10, 10, "#000");
            var path = r.path().attr({stroke: color, "stroke-width": 4, "stroke-linejoin": "round"}),
                bgp = r.path().attr({stroke: "none", opacity: .3, fill: color}),
                label = r.set(),
                lx = 0, ly = 0,
                is_label_visible = false,
                leave_timer,
                blanket = r.set();
            label.push(r.text(60, 12, "100% permissiveness").attr(txt));
            label.push(r.text(60, 27, "10000000 rules").attr(txt));
            label.hide();
            var frame = r.popup(100, 100, label, "right").attr({
                fill: "#000",
                stroke: "#666",
                "stroke-width": 2,
                "fill-opacity": .7
            }).hide();
            var p, bgpp;
            for (var i = 0, ii = rulesAmount.length; i < ii; i++) {
                var y = Math.round(height - bottomgutter - Y * permissiveness[i]),
                    x = Math.round(leftgutter + X * (i + .5)),
                    t = r.text(x, height - 6, rulesAmount[i]).toBack();
                if (!i) {
                    p = ["M", x, y, "C", x, y];
                    bgpp = ["M", leftgutter + X * .5, height - bottomgutter, "L", x, y, "C", x, y];
                }
                if (i && i < ii - 1) {
                    p = p.concat([x, y, x, y, x, y]);
                    bgpp = bgpp.concat([x, y, x, y, x, y]);
                }
                var dot = r.circle(x, y, 4).attr({fill: "#333", stroke: color, "stroke-width": 2});
                blanket.push(r.rect(leftgutter + X * i, 0, X, height - bottomgutter).attr({
                    stroke: "none",
                    fill: "#fff",
                    opacity: 0
                }));
                //create clickable areas at chart
                var rect = blanket[blanket.length - 1];
                (function (x, y, data, lbl, dot, lvl) {
                    //show label when hover over a point
                    rect.hover(function () {
                        clearTimeout(leave_timer);
                        var side = "right";
                        if (x + frame.getBBox().width > width) {
                            side = "left";
                        }
                        var ppp = r.popup(x, y, label, side, 1),
                            anim = Raphael.animation({
                                path: ppp.path,
                                transform: ["t", ppp.dx, ppp.dy]
                            }, 200 * is_label_visible);
                        lx = label[0].transform()[0][1] + ppp.dx;
                        ly = label[0].transform()[0][2] + ppp.dy;
                        frame.show().stop().animate(anim);
                        label[0].attr({text: data + "% permissiveness"}).show().stop().animateWith(frame, anim, {transform: ["t", lx, ly]}, 200 * is_label_visible);
                        label[1].attr({text: lbl + " rule" + (lbl == 1 ? "" : "s")}).show().stop().animateWith(frame, anim, {transform: ["t", lx, ly]}, 200 * is_label_visible);
                        dot.attr("r", 6);
                        is_label_visible = true;
                    }, function () {
                        dot.attr("r", 4);
                        leave_timer = setTimeout(function () {
                            frame.hide();
                            label[0].hide();
                            label[1].hide();
                            is_label_visible = false;
                        }, 1);
                    });
                    //Chart point click handler.
                    rect.click(function () {
                        var ids = [];
                        //get the initial data for table builing
                        $.ajax({
                            url: "/roots",
                            data: {'lvl': lvl},
                            dataType: 'json',
                            type: "POST",
                            complete: function (result) {
                                //hide the chart
                                $('#holder').hide();
                                //show the table
                                $('.header').show();
                                $('.table').show();
                                //show button to download the result
                                $('#download').show();
                                $('#download_raw').show();
                                $('#to_chart').show();
                                var points = jQuery.parseJSON(result.responseText);
                                $.each(points, function (key, value) {
                                    ids.push(value.id);
                                });
                                //build the tree
                                BuildTree(ids);
                                //method to build the initial table structure
                                treeTable.on('ready.jstree', function () {
                                    var data = JSON.stringify(ids);
                                    var tableHtml = '';
                                    var col2 = '',
                                        col3 = '',
                                        col4 = '',
                                        col5 = '',
                                        col6 = '',
                                        col7 = '',
                                        col8 = '';
                                    $.ajax({
                                        url: "/contents",
                                        data: {'id': data},
                                        dataType: 'json',
                                        type: "POST",
                                        complete: function (result) {
                                            var rows = jQuery.parseJSON(result.responseText);
                                            $.each(rows, function (key, value) {
                                                //col2 += '<div class="column-row table-contents" data-colid="2" data-name="order" data-id="' + value.id + '">' + value.id + '</div>';
                                                col3 += '<div class="column-row table-contents" data-colid="3" data-name="source" data-id="' + value.id + '">' + value.source + '</div>';
                                                col4 += '<div class="column-row table-contents" data-colid="4" data-name="destination" data-id="' + value.id + '">' + value.destination + '</div>';
                                                col5 += '<div class="column-row table-contents" data-colid="5" data-name="port" data-id="' + value.id + '">' + value.port + '</div>';
                                                col6 += '<div class="column-row table-contents" data-colid="6" data-name="protocol" data-id="' + value.id + '">' + value.protocol + '</div>';
                                                col7 += '<div class="column-row table-contents" data-colid="7" data-name="hits" data-id="' + value.id + '">' + value.hits + '</div>';
                                                col8 += '<div class="column-row table-contents" data-colid="8" data-name="permissiveness" data-id="' + value.id + '">' + value.permissiveness + '</div>';
                                            });
                                            //tableHtml += '<div class="table__second-column column">' + col2 + '</div>';
                                            tableHtml += '<div class="table__third-column column">' + col3 + '</div>';
                                            tableHtml += '<div class="table__fourth-column column">' + col4 + '</div><div class="table__fifth-column column">' + col5 + '</div>';
                                            tableHtml += '<div class="table__sixth-column column">' + col6 + '</div><div class="table__seventh-column column">' + col7 + '</div><div class="table__eighth-column column">' + col8 + '</div>';
                                            $('.table__first-column.content').after(tableHtml);
                                        }
                                    });
                                });
                                //method to add fields to table when any node opens
                                treeTable.on('after_open.jstree', function (e, node) {
                                    $('[data-id="' + node.node.id + '"]').css("color","grey");
                                    var col2 = '',
                                        col3 = '',
                                        col4 = '',
                                        col5 = '',
                                        col6 = '',
                                        col7 = '',
                                        col8 = '';
                                    $.ajax({
                                        url: "/contents",
                                        data: {'parent': node.node.id},
                                        dataType: 'json',
                                        type: "POST",
                                        complete: function (result) {
                                            var rows = jQuery.parseJSON(result.responseText);
                                            $.each(rows, function (key, value) {
                                                //col2 += '<div class="column-row table-contents" data-colid="2" data-parent="' + node.node.id + '" data-name="order" data-id="' + value.id + '">' + value.id + '</div>';
                                                col3 += '<div class="column-row table-contents" data-colid="3" data-parent="' + node.node.id + '" data-name="source" data-id="' + value.id + '">' + value.source + '</div>';
                                                col4 += '<div class="column-row table-contents" data-colid="4" data-parent="' + node.node.id + '" data-name="destination" data-id="' + value.id + '">' + value.destination + '</div>';
                                                col5 += '<div class="column-row table-contents" data-colid="5" data-parent="' + node.node.id + '" data-name="port" data-id="' + value.id + '">' + value.port + '</div>';
                                                col6 += '<div class="column-row table-contents" data-colid="6" data-parent="' + node.node.id + '" data-name="protocol" data-id="' + value.id + '">' + value.protocol + '</div>';
                                                col7 += '<div class="column-row table-contents" data-colid="7" data-parent="' + node.node.id + '" data-name="hits" data-id="' + value.id + '">' + value.hits + '</div>';
                                                col8 += '<div class="column-row table-contents" data-colid="8" data-parent="' + node.node.id + '" data-name="permissiveness" data-id="' + value.id + '">' + value.permissiveness + '</div>';
                                            });
                                            //$('*[data-name="order"][data-id="' + node.node.id + '"]').after(col2);
                                            $('*[data-name="source"][data-id="' + node.node.id + '"]').after(col3);
                                            $('*[data-name="destination"][data-id="' + node.node.id + '"]').after(col4);
                                            $('*[data-name="port"][data-id="' + node.node.id + '"]').after(col5);
                                            $('*[data-name="protocol"][data-id="' + node.node.id + '"]').after(col6);
                                            $('*[data-name="hits"][data-id="' + node.node.id + '"]').after(col7);
                                            $('*[data-name="permissiveness"][data-id="' + node.node.id + '"]').after(col8);
                                        }
                                    });
                                });
                                //methods to remove unnecessary data from table
                                var allLi1;
                                var allLi2;
                                var allid1 = [];
                                var allid2 = [];

                                treeTable.on('close_node.jstree', function () {
                                    allid1 = [];
                                    //get ids of all lines before node is closed
                                    allLi1 = $("li");
                                    $.each(allLi1, function (key, value) {
                                        allid1.push(value.id);
                                    });
                                    console.log(allid1);
                                });
                                treeTable.on('after_close.jstree', function (e, node) {
                                    $('[data-id="' + node.node.id + '"]').css("color","black");
                                    allid2 = [];
                                    //remove information about opened children
                                    var tree = treeTable.jstree(true);
                                    tree.delete_node(node.node.children);
                                    tree._model.data[node.node.id].state.loaded = false;
                                    //get ids of all lines after node is closed
                                    allLi2 = $("li");
                                    $.each(allLi2, function (key, value) {
                                        allid2.push(value.id);
                                    });
                                    console.log(allid2);
                                    //get ids to remove from the page
                                    var diff = difference(allid1, allid2);
                                    console.log(diff);
                                    $.each(diff, function (key, value) {
                                        //remove all unnecessary lines
                                        $('.table-contents[data-id="' + value + '"]').remove();
                                    });
                                });
                            }
                        });
                    });
                })(x, y, permissiveness[i], rulesAmount[i], dot, level[i]);
            }
            //methods from the example to handle the chart
            p = p.concat([x, y, x, y]);
            bgpp = bgpp.concat([x, y, x, y, "L", x, height - bottomgutter, "z"]);
            path.attr({path: p});
            bgp.attr({path: bgpp});
            frame.toFront();
            label[0].toFront();
            label[1].toFront();
            blanket.toFront();
        }
    });
};
// This function is needed for the chart creation
(function () {
    var tokenRegex = /\{([^\}]+)\}/g,
        objNotationRegex = /(?:(?:^|\.)(.+?)(?=\[|\.|$|\()|\[('|")(.+?)\2\])(\(\))?/g, // matches .xxxxx or ["xxxxx"] to run over object properties
        replacer = function (all, key, obj) {
            var res = obj;
            key.replace(objNotationRegex, function (all, name, quote, quotedName, isFunc) {
                name = name || quotedName;
                if (res) {
                    if (name in res) {
                        res = res[name];
                    }
                    typeof res == "function" && isFunc && (res = res());
                }
            });
            res = (res == null || res == obj ? all : res) + "";
            return res;
        },
        fill = function (str, obj) {
            return String(str).replace(tokenRegex, function (all, key) {
                return replacer(all, key, obj);
            });
        };
    Raphael.fn.popup = function (X, Y, set, pos, ret) {
        pos = String(pos || "top-middle").split("-");
        pos[1] = pos[1] || "middle";
        var r = 5,
            bb = set.getBBox(),
            w = Math.round(bb.width),
            h = Math.round(bb.height),
            x = Math.round(bb.x) - r,
            y = Math.round(bb.y) - r,
            gap = Math.min(h / 2, w / 2, 10),
            shapes = {
                top: "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}l-{right},0-{gap},{gap}-{gap}-{gap}-{left},0a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
                bottom: "M{x},{y}l{left},0,{gap}-{gap},{gap},{gap},{right},0a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
                right: "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}l0-{bottom}-{gap}-{gap},{gap}-{gap},0-{top}a{r},{r},0,0,1,{r}-{r}z",
                left: "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}l0,{top},{gap},{gap}-{gap},{gap},0,{bottom}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z"
            },
            mask = [{
                x: x + r,
                y: y,
                w: w,
                w4: w / 4,
                h4: h / 4,
                right: 0,
                left: w - gap * 2,
                bottom: 0,
                top: h - gap * 2,
                r: r,
                h: h,
                gap: gap
            }, {
                x: x + r,
                y: y,
                w: w,
                w4: w / 4,
                h4: h / 4,
                left: w / 2 - gap,
                right: w / 2 - gap,
                top: h / 2 - gap,
                bottom: h / 2 - gap,
                r: r,
                h: h,
                gap: gap
            }, {
                x: x + r,
                y: y,
                w: w,
                w4: w / 4,
                h4: h / 4,
                left: 0,
                right: w - gap * 2,
                top: 0,
                bottom: h - gap * 2,
                r: r,
                h: h,
                gap: gap
            }][pos[1] == "middle" ? 1 : (pos[1] == "top" || pos[1] == "left") * 2];
        var dx = 0,
            dy = 0,
            out = this.path(fill(shapes[pos[0]], mask)).insertBefore(set);
        switch (pos[0]) {
            case "top":
                dx = X - (x + r + mask.left + gap);
                dy = Y - (y + r + h + r + gap);
                break;
            case "bottom":
                dx = X - (x + r + mask.left + gap);
                dy = Y - (y - gap);
                break;
            case "left":
                dx = X - (x + r + w + r + gap);
                dy = Y - (y + r + mask.top + gap);
                break;
            case "right":
                dx = X - (x - gap);
                dy = Y - (y + r + mask.top + gap);
                break;
        }
        out.translate(dx, dy);
        if (ret) {
            ret = out.attr("path");
            out.remove();
            return {
                path: ret,
                dx: dx,
                dy: dy
            };
        }
        set.translate(dx, dy);
        return out;
    };
})();
//select the div
var treeTable = $('#tree_table');
//Now it creates a tree from an exact chart point. (no way to get more general rules without returning to graph)
function BuildTree(id) {
    var data = JSON.stringify(id);
    treeTable.jstree({
        plugins: ["core"
        ],
        //config for Plugin to create a table from a tree. set headers and data source for table
        core: {
            //get the initial table data or children of a certain node
            'data': {
                url: '/table',
                data: function (node) {
                    return {'node': node.id, 'id': data};
                },
                dataType: 'json',
                type: "POST"
            },
            'dblclick_toggle': false,
            'check_callback': false
        }
    });
}
//download button handler
$('#download_raw').click(function () {
    var allLi = $("li:not(.jstree-open)");
    var allid = [];
    $.each(allLi, function (key, value) {
        allid.push(value.id);
    });
    var idset = JSON.stringify(allid);
    $.ajax({
        url: "/rawfile",
        data: {'id': idset},
        dataType: 'json',
        type: "POST",
        complete: function () {
            window.location = '/rawfile';
        }
    });
});
//download human-readable file  button handler
$('#download').click(function () {
    var allLi = $("li:not(.jstree-open)");
    var allid = [];
    $.each(allLi, function (key, value) {
        allid.push(value.id);
    });
    var idset = JSON.stringify(allid);
    $.ajax({
        url: "/file",
        data: {'id': idset},
        dataType: 'json',
        type: "POST",
        complete: function () {
            window.location = '/file';
        }
    });
});
//back to upload form button handler
$('#to_main').click(function () {
    window.location = '/';
});
//back to chart button handler
$('#to_chart').click(function () {
    window.location = '/results';

});

//function to find difference between two arrays (returns what is in a1, and not in a2)
function difference(a1, a2) {
    var result = [];
    for (var i = 0; i < a1.length; i++) {
        if (a2.indexOf(a1[i]) === -1) {
            result.push(a1[i]);
        }
    }
    return result;
}


