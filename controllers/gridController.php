<?php

namespace gridController;

class GooberSoapClient {
    private string $url;
    private int $port;
    private int $placeId;
    private string $jobId;
    private string $year;
    private array $headers;
// year is a stirng incase u wanna do 2016"M" and stuff ykyk
    public function __construct(string $url, int $port, string $jobId, int $placeId, string $year = "2016") {
        $this->url = $url;
        $this->port = $port;
        $this->jobId = $jobId;
        $this->year = $year;
        $this->headers = [
            "Content-Type: application/xml",
            "User-Agent: GooberBlox/GridService"
        ];
    }

    public function scriptService(string $script): bool|string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_PORT => $this->port,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<?xml version=\"1.0\" encoding=\"UTF - 8\"?>\n      <SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:ns2=\"http://roblox.com/RCCServiceSoap\" xmlns:ns1=\"http://roblox.com/\" xmlns:ns3=\"http://roblox.com/RCCServiceSoap12\">\n      <SOAP-ENV:Body>\n          <ns1:OpenJob>\n              <ns1:job>\n                  <ns1:id>{$this->jobId}</ns1:id>\n                  <ns1:expirationInSeconds>9999999</ns1:expirationInSeconds>\n                  <ns1:category>0</ns1:category>\n                  <ns1:cores>1</ns1:cores>\n                  </ns1:job>\n                  <ns1:script>\n                    <ns1:name>Gameserver</ns1:name>\n                    <ns1:script>\n$script\n
            </ns1:script>\n                <ns1:arguments></ns1:arguments>\n            </ns1:script>\n        </ns1:OpenJob>\n</SOAP-ENV:Body>\n</SOAP-ENV:Envelope>",    "Content-Type: application/xml",
                CURLOPT_HTTPHEADER => $this->headers,
        ]);

        if($this->year=="2017") {
            $resp = curl_exec($curl); // TODO: fix this HACK, :pensive:
            $err = curl_error($curl);

            curl_close($curl);
            if($err) return $err;
            else return $resp;
        } else {
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return $err;
        }
        return $response;
    }
    }

    public function renderService($userId): bool|string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_PORT => $this->port,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
 CURLOPT_POSTFIELDS => "<?xml version=\"1.0\" encoding=\"UTF - 8\"?>\n<SOAP-ENV:Envelope\n\txmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\"\n\txmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\"\n\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n\txmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"\n\txmlns:ns2=\"http://roblox.com/RCCServiceSoap\"\n\txmlns:ns1=\"http://roblox.com/\"\n\txmlns:ns3=\"http://roblox.com/RCCServiceSoap12\">\n\t<SOAP-ENV:Body>\n\t\t<ns1:OpenJob>\n\t\t\t<ns1:job>\n\t\t\t\t<ns1:id>1313awadwadawdawdawd2</ns1:id>\n\t\t\t\t<ns1:expirationInSeconds>9999999</ns1:expirationInSeconds>\n\t\t\t\t<ns1:category>1</ns1:category>\n\t\t\t\t<ns1:cores>1</ns1:cores>\n\t\t\t</ns1:job>\n\t\t\t<ns1:script>\n\t\t\t\t<ns1:name>rea</ns1:name>\n\t\t\t\t<ns1:script>\ngame:GetService(\"ScriptContext\").ScriptsDisabled = true\nlocal plr = game.Players:CreateLocalPlayer(1)\nplr.CharacterAppearance = \"http://goober.biz/charapi/getequipped.ashx?id=$userId\"\nplr:LoadCharacter(false)\n\ngame:GetService(\"RunService\"):Run()\n\nplr.Character.Animate.Disabled = true \nplr.Character.Torso.Anchored = true\n\n-- Headshot Camera\nlocal FOV = 52.5\nlocal AngleOffsetX = 0\nlocal AngleOffsetY = 0\nlocal AngleOffsetZ = 0\n\nlocal CameraAngle = plr.Character.Head.CFrame * CFrame.new(AngleOffsetX, AngleOffsetY, AngleOffsetZ)\nlocal CameraPosition = plr.Character.Head.CFrame + Vector3.new(0, 0, 0) + (CFrame.Angles(0, -0.2, 0).lookVector.unit * 3)\n\nlocal Camera = Instance.new(\"Camera\", plr.Character)\nCamera.Name = \"ThumbnailCamera\"\nCamera.CameraType = Enum.CameraType.Scriptable\n\nCamera.CoordinateFrame = CFrame.new(CameraPosition.p, CameraAngle.p)\nCamera.FieldOfView = FOV\nworkspace.CurrentCamera = Camera\nprint(\"GB::RenderService Generating for userId $userId\")\nwait(5)\nprint(\"GB::RenderService Rendering Image\")\nrender = game:GetService(\"ThumbnailGenerator\"):Click(\"PNG\", 1024, 1024, true)\n\n-- Upload the render data to your PHP endpoint\nlocal HttpService = game:GetService(\"HttpService\")\nlocal endpointURL = \"https://localhost/v1/renders\"\nlocal success, response = pcall(function()\n    return HttpService:PostAsync(endpointURL, HttpService:JSONEncode({imageData = render}))\nend)\n\nif success then\n    print(\"Image uploaded successfully.\")\n    print(\"Response:\", response)\nelse\n    warn(\"Failed to upload image:\", response)\nend\n\n                    </ns1:script>\n\t\t\t\t<ns1:arguments></ns1:arguments>\n\t\t\t</ns1:script>\n\t\t</ns1:OpenJob>\n\t</SOAP-ENV:Body>\n</SOAP-ENV:Envelope>",       ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return $response;
        }

    }
}

