(function() {
    var addButton = document.getElementById("bc_add_concert");
    var list = document.getElementById("bc_concerts_list");
    var removedConcerts = document.getElementById("bc_removed_concerts");
    var concertsCount = document.getElementById("bc_concerts_count");
    var concertIds = document.getElementById("bc_concerts_ids");
    var currentNumber = parseInt(concertsCount.value, 10);
    var removeListener = function(e) {
        e.preventDefault();
        var parent = e.target.parentNode;
        var id = parent.getElementsByClassName("bc_concert_id");
        if(id.length) {
            if(removedConcerts.value.length) {
                removedConcerts.value += "," + id[0].value;
            }
            else {
                removedConcerts.value = id[0].value;
            }
        }

        var number = parseInt(parent.id.substr(11), 10);
        var concerts = concertIds.value.split(",");
        concertIds.value = concerts.filter(function(i) {
            return parseInt(i, 10) != number;
        }).join(',');

        list.removeChild(parent);
    };
    var addListener = function(e) {
        e.preventDefault();
        var concert_id = 'bc_concert' + ++currentNumber + "_";
        var li = document.createElement("li");
        var p1 = document.createElement("p");
        var p2 = document.createElement("p");
        var p3 = document.createElement("p");
        var label1 = document.createElement("label");
        var label2 = document.createElement("label");
        var label3 = document.createElement("label");
        var input1 = document.createElement("input");
        var input2 = document.createElement("input");
        var input3 = document.createElement("input");
        var button = document.createElement("button");
        var span = document.createElement("span");
        var wrapper = document.createElement("div");

        li.id = "bc_concert_" + currentNumber;
        li.className = "bc_concert";

        label1.appendChild(document.createTextNode("Datum "));
        input1.type = "text";
        input1.name = concert_id + "date";
        input1.className = "bc_concert_date";
        jQuery(input1).datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });
        label1.appendChild(input1);
        p1.className = "bc_concert_row";
        p1.appendChild(label1);
        wrapper.appendChild(p1);

        label2.appendChild(document.createTextNode("Ort "));
        input2.type = "text";
        input2.name = concert_id + "location";
        label2.appendChild(input2);
        p2.className = "bc_concert_row";
        p2.appendChild(label2);
        wrapper.appendChild(p2);

        label3.appendChild(document.createTextNode("Eintritt (CHF)"));
        input3.type = "number";
        input3.min = 0;
        input3.step = 1;
        input3.name = concert_id + "fee";
        label3.appendChild(input3);
        p3.className = "bc_concert_row fee";
        p3.appendChild(label3);
        wrapper.appendChild(p3);

        li.appendChild(wrapper);

        button.addEventListener("click", removeListener, false);
        button.className = "button";
        span.className= "dashicons dashicons-trash";
        button.appendChild(span);
        li.appendChild(button);
        list.appendChild(li);
        if(concertIds.value.length) {
            concertIds.value += "," + currentNumber;
        }
        else {
            concertIds.value = currentNumber;
        }
    };

    if(parseInt(concertsCount.value, 10) > 0) {
        var removeButtons = document.getElementsByClassName('bc_remove_concert');
        for(var i = 0; i < removeButtons.length; ++i) {
            removeButtons[i].addEventListener("click", removeListener, false);
        }
    }

    addButton.addEventListener("click", addListener, false);

    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['de']);
    jQuery('.bc_concert_date').datetimepicker({
        timeFormat: "HH:mm:ss",
        dateFormat: "yy-mm-dd"
    });
})();
