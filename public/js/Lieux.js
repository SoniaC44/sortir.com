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
}

function initLieux(id_ville){

    const $selectVille = document.getElementById("sortie_ville");
    let $selectLieux = document.getElementById('sortie_lieu');

    //si valeur choisie = "choisir une ville"
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

                //si on a récupéré des lieux ( on a rempli options !)
                if(options !== ""){
                    initAdresse();
                }else{
                    //si on a pas d'erreur mais pas de lieux pour la ville
                    //on disabled le select de lieux
                    //$selectLieux.disabled = true;

                    //on efface le contenu des inputs
                    document.getElementById('sortie_rue').value = "";
                    document.getElementById('sortie_longitude').value = "";
                    document.getElementById('sortie_latitude').value = "";
                    document.getElementById('sortie_codePostal').value = "";
                }

            })
            .catch(e=>{
                alert('Problème lors du chargement des lieux.');
            });

    }else{
        //$selectLieux.disabled = true;
        $selectLieux.innerHTML = "";
    }

}

function initAdresse(){

    const $selectLieu = document.getElementById("sortie_lieu");
    $selectLieu.disabled = false;

    const $optionSelected = $selectLieu.options[$selectLieu.selectedIndex];

    document.getElementById('sortie_rue').value = $optionSelected.dataset.rue;
    document.getElementById('sortie_longitude').value = $optionSelected.dataset.long;
    document.getElementById('sortie_latitude').value = $optionSelected.dataset.lat;
    document.getElementById('sortie_codePostal').value = $optionSelected.dataset.codep;

}