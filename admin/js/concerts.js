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

        li.id = "bc_concert_" + currentNumber;

        label1.appendChild(document.createTextNode("Datum "));
        input1.type = "text";
        input1.name = concert_id + "date";
        input1.className = "bc_concert_date";
        jQuery(input1).datetimepicker({
            timeFormat: "HH:mm:ss",
            dateFormat: "yy-mm-dd"
        });
        label1.appendChild(input1);
        p1.appendChild(label1);
        li.appendChild(p1);

        label2.appendChild(document.createTextNode("Ort "));
        input2.type = "text";
        input2.name = concert_id + "location";
        label2.appendChild(input2);
        p2.appendChild(label2);
        li.appendChild(p2);

        label3.appendChild(document.createTextNode("Eintritt "));
        input3.type = "number";
        input3.min = 0;
        input3.step = 1;
        input3.name = concert_id + "fee";
        label3.appendChild(input3);
        label3.appendChild(document.createTextNode("CHF"));
        p3.appendChild(label3);
        li.appendChild(p3);

        button.addEventListener("click", removeListener, false);
        button.textContent = "Auftritt entfernen";
        button.className = "button";
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
