<?php

namespace App\Http\Controllers\API;

use Laravel\Lumen\Routing\Controller as BaseController;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class BudgetController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function Index(Request $request)
    {
       
        $this->validate($request, [
            'cep' => 'required|integer|max:99999999',
            'structure' => 'required|string',
            'accountValue' => 'required|integer',
        ]);
       
        //Obtem endereço através do CEP.
        $address = $this->ViaCep($request->cep);
          
        if ($address == "error"){
           
            return response()->json(['status' => 400,
                                    'message' => 'Error']);

        }elseif($address == "Cep invalido!"){
            
            return response()->json(['status' => 401,
                                    'message' => 'Cep invalido!']);
        
        }
        
        //Obtem a longitude e latitude através do endereço
        $geoLocation = $this->googleGeo($address);

        if (!$geoLocation->lat ?? null){
            
            return response()->json(['status' => 401,
                                     'message' => 'Erro ao obter a longitude e latitude']);

        }

        //Merge do objeto adress e geoLocation
        $addressComplete = (object) array_merge((array) $address, (array) $geoLocation);

        //Obtem o orçamento através do endereço, valor de luz consumida e tipo de telhado.
        $orcamento77Sol = $this->Sol77($addressComplete, $request->structure, $request->accountValue);

        return  $orcamento77Sol;

    }

    function viaCep($cep)
    {
        $client = new Client(['base_uri' => 'viacep.com.br/ws/']);       
        $response = $client->request('GET', $cep. '/json');
        
        

        if ($response->getStatusCode() != 200){
            
            return "error";
        }
        
        $body = $response->getBody();
        $stringBody =  ( string )  $body ; 
        $address = json_decode($stringBody);
               
        if ($address->erro ?? null){
            return "Cep invalido!";
        }

        return $address;
    }

    function googleGeo($address)
    {
        //Example:  "1600 Amphitheatre Parkway, Mountain View, CA";
        $addressGeo = $address->logradouro . "," . $address->localidade . "," . $address->uf;
        
        $APY_KEY = "AIzaSyDsFa36pvFAaAFJ8FCjisi66-p2lwpflbw";

        $base_url = "https://maps.googleapis.com/maps/api/geocode/json?";

        $client2 = new Client(['base_uri' => $base_url . 'address='. $addressGeo . '&key=' . $APY_KEY]);
      
        $response = $client2->request('GET');
        
        $body = $response->getBody();
        $stringBody =  ( string )  $body ; 
        $objectBody = json_decode($stringBody);

        if ($objectBody->status == "OK"){

            $location = $objectBody->results[0]->geometry->location; //lat //lng  

            return $location;
        }

        return "error";
    }

    function Sol77($address, $structure, $accountValue)
    {  
        $state     = $address->uf;
        $city      = $address->localidade;        
        $cep       = $address->cep;
        $latitude  = $address->lat;
        $longitude = $address->lng;

        $base_url = "https://api.77sol.com.br/busca-cep?";

        $client3 = new Client(['base_uri' => $base_url 
                            . '&estrutura='. $structure
                            . '&estado='. $state
                            . '&cidade='. $city
                            . '&valor_conta='. $accountValue
                            . '&cep='. $cep
                            . '&latitude='. $latitude
                            . '&longitude='. $longitude
                            ]);
        
        $response = $client3->request('GET');
        $body = $response->getBody();
 
        return $body;
        
        //https://api.77sol.com.br/busca-cep?estrutura=fibrocimento-madeira&estado=SP&cidade=Santana%20de%20Parna%C3%ADba&valor_conta=200&cep=06543-001&latitude=-23.5&longitude=-46.9
    }

    
}
