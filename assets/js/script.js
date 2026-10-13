let selectedSection = null;
function changeColor(who){
    let color = $(who).data('color') ? $(who).data('color') : "white" ;
    $(`.${selectedSection}-color`).removeClass().addClass(`${selectedSection}-color ${color}`);
}
function showColorSelector(section=""){
    if(section === selectedSection || section === ""){
        $("#color-selector-container, .color-selector-nav-button").removeClass("active");
        selectedSection = null;
    }else if(section !== ""){
        selectedSection = section;
        $(".color-selector").removeClass("active");
        $("#color-select-"+section).addClass("active");
        $(".color-selector-nav-button").removeClass("active");
        $("#color-selector-container").addClass("active");
        $(".color-selector-nav-button."+section).addClass("active");
    }
    console.log(section, selectedSection);
}
/*
if(section == "roof"){
    $("#color-select-label").text("Selecciona el color del techo");
}else if(section == "walls"){
    $("#color-select-label").text("Selecciona el color de las paredes");
}else{
    $("#color-select-label").text("Selecciona el color de las ventanas");
}
*/