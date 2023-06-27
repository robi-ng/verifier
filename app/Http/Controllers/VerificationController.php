<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class VerificationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $file = $request->file("file");
        $content = json_decode(file_get_contents($file->getRealPath()), true);

        $recipient_validator = Validator::make($content, [
            "data.recipient.name" => "required",
            "data.recipient.email" => "required",
        ]);

        if ($recipient_validator->fails()) {
            return $this->store_result_and_respond("invalid_recipient");
        }

        $issuer_validator = Validator::make($content, [
            "data.issuer.name" => "required",
            "data.issuer.identityProof.key" => "required",
            "data.issuer.identityProof.location" => "required",
        ]);

        if ($recipient_validator->fails() || !$this->is_valid_issuer($content)) {
            return $this->store_result_and_respond("invalid_issuer");
        }

        $flatten_data = $this->flatten($content["data"]);
        $target_hash = $this->compute_target_hash($flatten_data);

        $target_hash_validator = Validator::make($content, [
            "signature.targetHash" => "required",
        ]);

        if ($target_hash_validator->fails() || $content["signature"]["targetHash"] != $target_hash) {
            return $this->store_result_and_respond("invalid_signature");
        }

        return $this->store_result_and_respond("verified", $content["data"]["issuer"]["name"]);
    }

    private function store_result_and_respond(string $result, string $issuer = null) {
        Verification::create([
            "user_id" => Auth::user()->id,
            "file_type" => "json",
            "verification_result" => $result,
        ]);
        if ($result == "verified") {
            return response()->json([
                "data" => [
                    "issuer" => $issuer,
                    "result" => $result,
                ]], 200);
        } 
        return response()->json(["error_code" => $result], 200);
    }

    private function is_valid_issuer(array $array) {
        $key = $array["data"]["issuer"]["identityProof"]["key"];
        $location = $array["data"]["issuer"]["identityProof"]["location"];
        $google_dns = "https://dns.google/resolve?name=" . $location . "&type=TXT";
        $dns_response = json_decode(file_get_contents($google_dns), true);
        $answer_array = $dns_response["Answer"];

        $key = $array["data"]["issuer"]["identityProof"]["key"];
        $expected_record = "openatts a=dns-did; p=" . $key . "; v=1.0;";

        foreach ($answer_array as $record) {
            if ($record["data"] == $expected_record) {
                return true;
            }
        }
        return false;
    }

    private function flatten(array $array, string $prefix = "") {
        $result = array();
        foreach ($array as $key=>$value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . ".");
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    private function compute_target_hash(array $flatten_data) {
        $hash_sources = array();
        foreach ($flatten_data as $key=>$value) {
            $hash_source = '{"' . $key . '":"' . $value . '"}';
            $hash_sources[] = hash("sha256", $hash_source);
        }
        sort($hash_sources);
        return hash("sha256", json_encode($hash_sources));
    }

}