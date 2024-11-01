var date_labels = jQuery('#date_labels').val();
var date_labels_array = date_labels.split(',');

var newLabelsArray = [];

// iterate over the dates list from above
for (let i = 0; i < date_labels_array.length; i++) {
    // pass the date at index i into moment
    let date = moment(date_labels_array[i]);
    // add this new date to the newLabelsArray
    newLabelsArray.push(date);
}
var config = {
    type: 'line',
    data: {
        labels: newLabelsArray,
        datasets: arrayFromPhp
    },
    options: {
        legend: {
            display: false
        },
        aspectRatio: 3.5,
        responsive: true,
        title: {
            display: true,
            text: 'Social Proof Analytics'
        },
        tooltips: {
            mode: 'label',
        },
        hover: {
            mode: 'nearest',
            intersect: true
        },
        scales: {
            xAxes: [{
                type:       "time",
                time:       {
                    unit: 'day',
                    unitStepSize: 1,
                    tooltipFormat: 'MMM DD',
                    displayFormats: {
                        'day': 'MMM DD'
                    }
                },
                scaleLabel: {
                    display:     true,
                    labelString: 'Date'
                },
                gridLines: {
                    display:false
                }
            }],
            yAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: 'No. of clicks'
                }
            }]
        }
    }
};

var ctx = document.getElementById("wooproof-analytics").getContext("2d");
new Chart(ctx, config);