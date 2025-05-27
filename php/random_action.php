<?php

class ActionCardHandler {
    private $mysqli;
    private $max_drawn_cards;
    
    public function __construct($mysqli, $max_drawn_cards = 5) {
        $this->mysqli = $mysqli;
        $this->max_drawn_cards = $max_drawn_cards;
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['drawn_card_ids'])) {
            $_SESSION['drawn_card_ids'] = [];
        }
    }
    
    public function getRandomActionCard() {
        if ($this->mysqli->connect_errno) {
            error_log("Błąd połączenia z MySQL: " . $this->mysqli->connect_error);
            return null;
        }
        
        if (count($_SESSION['drawn_card_ids']) >= $this->max_drawn_cards) {
            $removed_card_id = array_shift($_SESSION['drawn_card_ids']);
            error_log("Karta o ID: " . $removed_card_id . " wróciła do puli.");
        }
        
        $drawnIds = $_SESSION['drawn_card_ids'];
        $total_available_cards = $this->getAvailableCardCount($drawnIds);
        
        if ($total_available_cards === 0) {
            error_log("Brak dostępnych kart akcji do wylosowania. Resetowanie puli.");
            $_SESSION['drawn_card_ids'] = [];
            $total_available_cards = $this->getTotalCardCount();
            
            if ($total_available_cards === 0) {
                error_log("Brak kart w bazie danych!");
                return null;
            }
            $drawnIds = [];
        }
        
        $action_card = $this->drawRandomCard($drawnIds, $total_available_cards);
        
        if ($action_card) {
            $_SESSION['drawn_card_ids'][] = $action_card->id;
            return $action_card;
        }
        
        return null;
    }
    
    public function executeCardEffect($card, $player_id) {
        if (!$card || !isset($card->effect_json)) {
            return ['success' => false, 'message' => 'Nieprawidłowa karta'];
        }
        
        $effect = json_decode($card->effect_json, true);
        if (!$effect) {
            return ['success' => false, 'message' => 'Nieprawidłowy format efektu karty'];
        }
        
        $result = ['success' => true, 'message' => $card->description, 'effects' => []];
        
        switch ($effect['type']) {
            case 'move':
                $result['effects'][] = [
                    'type' => 'move',
                    'direction' => $effect['direction'],
                    'value' => $effect['value']
                ];
                break;
                
            case 'change_stat':
                $result['effects'][] = [
                    'type' => 'stat_change',
                    'stat' => $effect['stat'],
                    'value' => $effect['value'],
                    'player_id' => $player_id
                ];
                break;
                
            case 'change_coins':
                $result['effects'][] = [
                    'type' => 'coin_change',
                    'value' => $effect['value'],
                    'player_id' => $player_id
                ];
                break;
                
            case 'change_turns_to_miss':
                $result['effects'][] = [
                    'type' => 'skip_turns',
                    'value' => $effect['value'],
                    'player_id' => $player_id
                ];
                break;
                
            default:
                $result['success'] = false;
                $result['message'] = 'Nieznany typ efektu karty';
        }
        
        return $result;
    }
    
    public function handleSurpriseField($player_id) {
        $card = $this->getRandomActionCard();
        
        if (!$card) {
            return [
                'success' => false,
                'message' => 'Nie udało się wylosować karty akcji',
                'card' => null,
                'effects' => []
            ];
        }
        
        $effect_result = $this->executeCardEffect($card, $player_id);
        
        return [
            'success' => $effect_result['success'],
            'message' => $effect_result['message'],
            'card' => [
                'id' => $card->id,
                'name' => $card->name,
                'description' => $card->description
            ],
            'effects' => $effect_result['effects'] ?? []
        ];
    }
    
    private function getAvailableCardCount($drawnIds) {
        $sql = 'SELECT COUNT(id) AS card_count FROM action_cards';
        $params = [];
        $types = '';
        
        if (!empty($drawnIds)) {
            $placeholders = implode(',', array_fill(0, count($drawnIds), '?'));
            $sql .= ' WHERE id NOT IN (' . $placeholders . ')';
            $params = $drawnIds;
            $types = str_repeat('i', count($drawnIds));
        }
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . $this->mysqli->error);
            return 0;
        }
        
        if (!empty($drawnIds)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['card_count'] ?? 0;
    }
    
    private function getTotalCardCount() {
        $stmt = $this->mysqli->prepare("SELECT COUNT(id) AS total FROM action_cards");
        if (!$stmt) {
            return 0;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['total'] ?? 0;
    }
    
    private function drawRandomCard($drawnIds, $total_available_cards) {
        $random_offset = mt_rand(0, $total_available_cards - 1);
        
        $sql = 'SELECT id, name, description, effect_json FROM action_cards';
        $params = [];
        $types = '';
        
        if (!empty($drawnIds)) {
            $placeholders = implode(',', array_fill(0, count($drawnIds), '?'));
            $sql .= ' WHERE id NOT IN (' . $placeholders . ')';
            $params = $drawnIds;
            $types = str_repeat('i', count($drawnIds));
        }
        
        $sql .= ' ORDER BY id LIMIT 1 OFFSET ?';
        $params[] = $random_offset;
        $types .= 'i';
        
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . $this->mysqli->error);
            return null;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $card = $result->fetch_object();
        $stmt->close();
        
        return $card;
    }
    
    public function resetDrawnCards() {
        $_SESSION['drawn_card_ids'] = [];
    }
    
    public function getDrawnCardIds() {
        return $_SESSION['drawn_card_ids'] ?? [];
    }
    
    public function getCardById($card_id) {
        $stmt = $this->mysqli->prepare("SELECT id, name, description, effect_json FROM action_cards WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param('i', $card_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $card = $result->fetch_object();
        $stmt->close();
        
        return $card;
    }
}

?>