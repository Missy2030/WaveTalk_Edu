<?php
session_start();
require_once '../../private/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

try {
    // Récupérer le quiz
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        echo json_encode(['success' => false, 'error' => 'Quiz non trouvé']);
        exit();
    }
    
    // Récupérer les questions
    $stmt = $pdo->prepare("
        SELECT qq.* 
        FROM quiz_questions qq 
        WHERE qq.quiz_id = ? 
        ORDER BY RAND() 
        LIMIT 10
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    
    // Formater les questions
    $formattedQuestions = [];
    foreach ($questions as $question) {
        $options = [
            $question['option_a'],
            $question['option_b'],
            $question['option_c'],
            $question['option_d']
        ];
        
        $formattedQuestions[] = [
            'id' => $question['id'],
            'text' => $question['question_text'],
            'options' => $options,
            'correct_answer' => $question['correct_option'] - 1, // 0-indexed
            'explanation' => $question['explanation']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'questions' => $formattedQuestions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>