<?php

namespace Tests\Feature;

use App\Libraries\WhatsAppClient;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Config\Store;
use Config\WhatsApp;
use Tests\Support\Traits\StoreFixtures;

final class WhatsAppWebhookTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StoreFixtures;

    private const SECRET   = 'test-app-secret';
    private const CUSTOMER = '919847012345';

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = null;

    private WhatsAppClient $outbox;
    private int $messageId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $config                = config(WhatsApp::class);
        $config->phoneNumberId = '123456';
        $config->accessToken   = 'test-access-token';
        $config->appSecret     = self::SECRET;
        $config->verifyToken   = 'test-verify-token';

        $this->outbox = new class () extends WhatsAppClient {
            /** @var list<array{0: string, 1: array<string, mixed>}> */
            public array $sent = [];

            public function send(string $to, array $reply): void
            {
                $this->sent[] = [$to, $reply];
            }
        };

        Services::injectMock('whatsapp', $this->outbox);
    }

    public function testMetaCanVerifyTheCallbackUrl(): void
    {
        $result = $this->get('webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=1158201444');

        $result->assertOK();
        $this->assertSame('1158201444', $result->response()->getBody());
    }

    public function testAWrongVerifyTokenIsRefused(): void
    {
        $this->get('webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=1')->assertStatus(403);
        $this->get('webhooks/whatsapp?hub.mode=subscribe&hub.verify_token[]=x&hub.challenge=1')->assertStatus(403);
    }

    public function testNothingIsAcceptedUntilConnected(): void
    {
        $config              = config(WhatsApp::class);
        $config->verifyToken = '';
        $config->appSecret   = '';

        $this->get('webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=1')->assertStatus(403);

        // Signed with the empty secret, which anyone could do.
        $this->signed($this->payload([$this->textMessage('hi')]), '')->assertStatus(403);

        $this->assertSame([], $this->outbox->sent);
    }

    public function testAForgedMessageIsRefused(): void
    {
        $this->signed($this->payload([$this->textMessage('hi')]), 'not-the-secret')->assertStatus(403);

        $this->assertSame([], $this->outbox->sent);
    }

    public function testAMessageIsAnsweredWithoutACsrfToken(): void
    {
        $this->deliver($this->textMessage('hello'))->assertOK();

        $this->assertCount(1, $this->outbox->sent);
        $this->assertSame(self::CUSTOMER, $this->outbox->sent[0][0]);
    }

    public function testATextThatLooksLikeBadEncodingIsStillAnswered(): void
    {
        $this->deliver($this->textMessage('Do you have 20%ab offers?'))->assertOK();

        $this->assertCount(1, $this->outbox->sent);
    }

    public function testAGreetingGetsTheMenuWithinWhatsAppLimits(): void
    {
        $this->deliver($this->textMessage('hello'));

        $menu = $this->lastReply()['interactive'];
        $rows = $menu['action']['sections'][0]['rows'];

        $this->assertSame('list', $menu['type']);
        $this->assertStringContainsString('automated assistant', $menu['body']['text']);
        $this->assertLessThanOrEqual(20, strlen($menu['action']['button']));
        $this->assertSame(['custom', 'bridal', 'draping', 'shop', 'order', 'visit', 'team'], array_column($rows, 'id'));

        foreach ($rows as $row) {
            $this->assertLessThanOrEqual(24, strlen($row['title']));
            $this->assertLessThanOrEqual(72, strlen($row['description']));
        }
    }

    public function testChoosingFromTheMenuAnswersIt(): void
    {
        $this->deliver($this->choice('visit'));

        $body = $this->lastText();

        $this->assertStringContainsString('BCG Spring Villa', $body);
        $this->assertStringContainsString('google.com/maps/search', $body);
        $this->assertStringContainsString('+91 98476 54918', $body);
    }

    public function testVisitFallsBackToTheMenuWithoutAnAddress(): void
    {
        config(Store::class)->address = '';

        $this->deliver($this->choice('visit'));

        $rows = $this->lastReply()['interactive']['action']['sections'][0]['rows'];

        $this->assertNotContains('visit', array_column($rows, 'id'));
    }

    public function testShopSendsTheCollectionLink(): void
    {
        $this->deliver($this->choice('shop'));

        $this->assertStringContainsString(url_to('shop'), $this->lastText());
    }

    public function testAnOrderNumberGetsItsStatusAndNothingPrivate(): void
    {
        $order = $this->placeOrder([[$this->makeProduct(), 1]]);

        $this->deliver($this->textMessage(strtolower($order->number)));

        $body = $this->lastText();

        $this->assertStringContainsString($order->number, $body);
        $this->assertStringContainsString('Status: New', $body);
        $this->assertStringContainsString(url_to('orders.show', $order->number), $body);
        $this->assertStringNotContainsString('Asha', $body);
        $this->assertStringNotContainsString('Beach Road', $body);
    }

    public function testAnUnknownOrderNumberSaysSo(): void
    {
        $this->deliver($this->textMessage('ZZZZZ22222'));

        $this->assertStringContainsString('could not find that order', $this->lastText());
    }

    public function testATenLetterWordInASentenceIsNotTreatedAsAnOrder(): void
    {
        $this->deliver($this->textMessage('You are a great dressmaker'));

        $this->assertSame('interactive', $this->lastReply()['type']);
    }

    /**
     * @dataProvider siteMessages
     */
    public function testMessagesPreparedByTheSiteReachTheRightAnswer(string $message, string $expected): void
    {
        $this->deliver($this->textMessage($message));

        $this->assertStringContainsString($expected, $this->lastText());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function siteMessages(): array
    {
        return [
            'chat'    => ["Hi RUA, I'd like to ask about a bespoke piece.", 'Tell us the occasion'],
            'custom'  => ["Hi RUA, I'd like a custom piece.", 'Tell us the occasion'],
            'bridal'  => ["Hi RUA, I'd like to book a bridal consultation.", 'Tell us your wedding date'],
            'product' => ["Hi RUA, I'm interested in Bridal Saree: https://example.com/products/bridal-saree", 'asking about this piece'],
        ];
    }

    public function testTheBotStaysQuietAfterHandingOverUntilAskedForTheMenu(): void
    {
        $this->deliver($this->choice('team'));
        $this->assertCount(1, $this->outbox->sent);

        $this->deliver($this->textMessage('Here is my question for the team'));
        $this->assertCount(1, $this->outbox->sent);

        $this->deliver($this->textMessage('Menu'));
        $this->assertCount(2, $this->outbox->sent);
        $this->assertSame('interactive', $this->lastReply()['type']);
    }

    public function testAPhotoGetsTheMenu(): void
    {
        $this->deliver(['from' => self::CUSTOMER, 'id' => 'wamid.photo', 'type' => 'image', 'image' => ['id' => '1']]);

        $this->assertSame('interactive', $this->lastReply()['type']);
    }

    public function testARedeliveredMessageIsAnsweredOnce(): void
    {
        $message = $this->textMessage('hello');

        $this->deliver($message);
        $this->deliver($message);

        $this->assertCount(1, $this->outbox->sent);
    }

    public function testDeliveryReceiptsGetNoReply(): void
    {
        $body = json_encode(['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.1', 'status' => 'read']]]]]]]]);

        $this->signed($body)->assertOK();

        $this->assertSame([], $this->outbox->sent);
    }

    /**
     * @param array<string, mixed> ...$messages
     */
    private function deliver(array ...$messages): TestResponse
    {
        return $this->signed($this->payload($messages));
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    private function payload(array $messages): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry'  => [['id' => '1', 'changes' => [[
                'field' => 'messages',
                'value' => ['messaging_product' => 'whatsapp', 'messages' => $messages],
            ]]]],
        ]);
    }

    private function signed(string $body, string $secret = self::SECRET): TestResponse
    {
        return $this->withBody($body)
            ->withHeaders(['X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, $secret)])
            ->post('webhooks/whatsapp');
    }

    /**
     * @return array<string, mixed>
     */
    private function textMessage(string $body): array
    {
        return ['from' => self::CUSTOMER, 'id' => 'wamid.' . ++$this->messageId, 'type' => 'text', 'text' => ['body' => $body]];
    }

    /**
     * @return array<string, mixed>
     */
    private function choice(string $id): array
    {
        return [
            'from'        => self::CUSTOMER,
            'id'          => 'wamid.' . ++$this->messageId,
            'type'        => 'interactive',
            'interactive' => ['type' => 'list_reply', 'list_reply' => ['id' => $id, 'title' => 'Chosen']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lastReply(): array
    {
        return $this->outbox->sent[array_key_last($this->outbox->sent)][1];
    }

    private function lastText(): string
    {
        return $this->lastReply()['text']['body'];
    }
}
