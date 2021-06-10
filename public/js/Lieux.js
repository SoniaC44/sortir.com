window.addEventListener('DOMContentLoaded', function () { init(); });

function init(){
    //ajout des evenements sur le change du select ville
    $selectVille = document.getElementById("sortie_ville");
    $selectVille.addEventListener("change",() => {
        initLieux($selectVille.options[$selectVille.selectedIndex].value);
        initAdresse()
    });

    //ajout des evenements sur le change du select Lieux
    $selectLieu = document.getElementById("sortie_lieu");
    $selectLieu.addEventListener("change",() => {
        initAdresse();
    });

    //ajout evenement click sur bouton+
    document.getElementById("btnPlus").onclick = function() {

        if (document.getElementById("nouveauLieu").style.display === "none"){
            document.getElementById("nouveauLieu").style.display = "block";
            document.getElementById("ancienLieu").style.display = "none";
        } else {
            document.getElementById("nouveauLieu").style.display = "none";
            document.getElementById("ancienLieu").style.display = "block";
        }
    }
}

function initLieux(id_ville){

    const $selectVille = document.getElementById("sortie_ville");

    //si valeur choisie != "choisir une ville"
    if($selectVille.options[$selectVille.selectedIndex].value !== ""){
        fetch(url + '/' + id_ville, {'method':'GET'})
            .then(response=>response.json())
            .then(response => {
                $selectLieux = document.getElementById('sortie_lieu');
                let options=`<option value="" >Choisir un lieu</option>`;
                response.map(lieu => {
                    options += `<option value="${lieu.id}" data-rue="${lieu.rue}" data-long="${lieu.longitude}" data-lat="${lieu.latitude}" data-codep="${lieu.codePostal}">${lieu.nom}</option>`;
                });
                $selectLieux.innerHTML = options;

                initAdresse();

            })
            .catch(e=>{
                alert('Problème lors du chargement des lieux.');
            });

    }else{

        document.getElementById('sortie_lieu').innerHTML = "";
        //on efface le contenu des inputs
        viderContenu();
    }

}

function initAdresse(){

    const $selectLieu = document.getElementById("sortie_lieu");

    const $optionSelected = $selectLieu.options[$selectLieu.selectedIndex];

    if($optionSelected !== undefined){

        if($optionSelected.dataset.rue !== undefined){
            document.getElementById('sortie_rue').value = $optionSelected.dataset.rue;
            document.getElementById('sortie_longitude').value = $optionSelected.dataset.long;
            document.getElementById('sortie_latitude').value = $optionSelected.dataset.lat;
            document.getElementById('sortie_codePostal').value = $optionSelected.dataset.codep;
        }else{
            //on efface le contenu des inputs
            viderContenu();
        }
    }
}

function viderContenu(){

    document.getElementById('sortie_rue').value = "";
    document.getElementById('sortie_longitude').value = "";
    document.getElementById('sortie_latitude').value = "";
    document.getElementById('sortie_codePostal').value = "";
}