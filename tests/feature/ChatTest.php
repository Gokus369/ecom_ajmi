<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Store;
use Tests\Support\Traits\StoreFixtures;

final class ChatTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    public function testEveryStorePageCarriesTheChat(): void
    {
        foreach (['/', 'shop', 'bespoke', 'cart'] as $uri) {
            $body = (string) $this->get($uri)->getBody();

            $this->assertStringContainsString('id="chat"', $body);
            $this->assertStringContainsString('js/chat.js', $body);
        }
    }

    public function testTheChatFormCarriesASecurityToken(): void
    {
        $body = (string) $this->get('shop')->getBody();
        $form = substr($body, strpos($body, 'class="chat-form"'));

        $this->assertStringContainsString('name="' . csrf_token() . '"', $form);
    }

    public function testWhatsAppStaysReachableWithoutJavaScript(): void
    {
        $body   = (string) $this->get('shop')->getBody();
        $footer = substr($body, strpos($body, '<footer'));

        $this->assertStringContainsString('<a href="https://wa.me/916282876142"', html_entity_decode($footer));
    }

    public function testTheLauncherStaysHiddenUntilTheScriptShowsIt(): void
    {
        $this->assertSame(1, preg_match('/<button class="chat-launcher"[^>]* hidden>/', (string) $this->get('shop')->getBody()));
    }

    public function testNoWhatsAppNumberMeansNoChat(): void
    {
        config(Store::class)->whatsapp = '';

        $this->assertStringNotContainsString('id="chat"', (string) $this->get('shop')->getBody());
        $this->submit('chat', ['message' => 'hello'])->assertStatus(404);
    }

    public function testAMessageNeedsTheSecurityToken(): void
    {
        $this->post('chat', ['message' => 'hello'])->assertRedirect();
    }

    public function testAGreetingGetsTheMenu(): void
    {
        $reply = $this->ask('hello');

        $this->assertStringContainsString('automated assistant', $reply['text']);
        $this->assertSame(
            ['Custom piece', 'Bridal consultation', 'Saree draping', 'Shop the collection', 'Track my order', 'Visit the boutique', 'Talk to the team'],
            array_column($reply['options'], 'title'),
        );
    }

    public function testChoosingVisitGivesTheBoutiqueDetails(): void
    {
        $reply = $this->ask('visit');

        $this->assertStringContainsString('BCG Spring Villa', $reply['text']);
        $this->assertStringContainsString('google.com/maps/search', $reply['links'][0]['url']);
        $this->assertSame('tel:+919847654918', $reply['links'][1]['url']);
    }

    public function testAnOrderNumberGetsItsStatusAndNothingPrivate(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);
        $reply = $this->ask(strtolower($order->number));
        $json  = json_encode($reply);

        $this->assertStringContainsString('Status: New', $reply['text']);
        $this->assertSame(url_to('orders.show', $order->number), $reply['links'][0]['url']);
        $this->assertStringNotContainsString('Asha', $json);
        $this->assertStringNotContainsString('Beach Road', $json);
        $this->assertStringNotContainsString('98470', $json);
    }

    /**
     * @dataProvider handovers
     */
    public function testHandoversContinueOnWhatsAppWithAReadyMessage(string $message, string $ready): void
    {
        $reply = $this->ask($message);
        $link  = $reply['links'][array_key_last($reply['links'])];

        $this->assertSame('Continue on WhatsApp', $link['label']);
        $this->assertStringStartsWith('https://wa.me/916282876142?text=', $link['url']);
        $this->assertStringContainsString(rawurlencode($ready), $link['url']);
        $this->assertStringNotContainsString('reply here', $reply['text']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function handovers(): array
    {
        return [
            'custom' => ['custom', "Hi RUA, I'd like a custom piece."],
            'bridal' => ['bridal', "Hi RUA, I'd like to book a bridal consultation."],
            'team'   => ['team', "Hi RUA, I'd like to talk to the team."],
            'piece'  => ['I am interested in the ivory lehenga', 'I am interested in the ivory lehenga'],
        ];
    }

    public function testTheChatNeverRepeatsWhatTheCustomerTyped(): void
    {
        $this->assertStringNotContainsString('<script>', json_encode($this->ask('<script>alert(1)</script>'), JSON_UNESCAPED_SLASHES));
    }

    public function testOddInputStillGetsTheMenu(): void
    {
        $this->assertNotSame([], $this->ask(['not', 'a', 'string'])['options']);
        $this->assertNotSame([], $this->ask(str_repeat('x ', 5000))['options']);
    }

    /**
     * @param list<string>|string $message
     *
     * @return array<string, mixed>
     */
    private function ask(array|string $message): array
    {
        $result = $this->submit('chat', ['message' => $message]);

        $result->assertOK();

        return json_decode($result->response()->getBody(), true);
    }
}
