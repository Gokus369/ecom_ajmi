<?php

namespace Tests\Unit;

use App\Libraries\WhatsAppClient;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCURLRequest;
use Config\App;
use Config\Services;
use Config\WhatsApp;

final class WhatsAppClientTest extends CIUnitTestCase
{
    private MockCURLRequest $curl;

    protected function setUp(): void
    {
        parent::setUp();

        $config                = config(WhatsApp::class);
        $config->phoneNumberId = '123456';
        $config->accessToken   = 'test-access-token';

        $app        = config(App::class);
        $this->curl = new MockCURLRequest($app, new URI('https://graph.facebook.com/'), new Response($app));

        Services::injectMock('curlrequest', $this->curl);
    }

    public function testRepliesGoToTheCloudApiWithTheToken(): void
    {
        $this->curl->setOutput('{"messages":[{"id":"wamid.1"}]}');

        (new WhatsAppClient())->send('919847012345', ['type' => 'text', 'text' => ['body' => 'Hello']]);

        $options = $this->curl->curl_options;

        $this->assertSame('https://graph.facebook.com/v26.0/123456/messages', $options[CURLOPT_URL]);
        $this->assertContains('Authorization: Bearer test-access-token', $options[CURLOPT_HTTPHEADER]);
        $this->assertSame([
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => '919847012345',
            'type'              => 'text',
            'text'              => ['body' => 'Hello'],
        ], json_decode($options[CURLOPT_POSTFIELDS], true));
    }

    public function testARefusedMessageDoesNotBreakTheWebhook(): void
    {
        $this->curl->setOutput("HTTP/1.1 401 Unauthorized\r\n\r\n{}");

        (new WhatsAppClient())->send('919847012345', ['type' => 'text', 'text' => ['body' => 'Hello']]);

        $this->assertLogged('error', 'WhatsApp send failed with HTTP 401');
    }
}
