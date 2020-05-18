<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Form\DiscussionType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DiscussionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Reponses;
use App\Repository\ReponseRepository;
use App\Entity\Utilisateurs;
use App\Repository\UtilisateursRepository;

class ForumController extends AbstractController
{
    /**
     * @Route("/forum", name="forum")
     */
    public function accueil( DiscussionRepository $repo, PaginatorInterface $paginator, Request $request )
    {
    	if (count($repo->findAll()) == 0) {
    		return $this->render("forum/no_result.html.twig");
    	}
    	
    	$derniereDisc = $repo->findAllDesc();
        
        $derniereDisc = $paginator->paginate(
        		$derniereDisc,
        		$request->query->getInt("page", 1),
        		10
        );
        
        // On utilise un template basé sur Bootstrap, celui par défaut ne l'est pas
        $derniereDisc->setTemplate('@KnpPaginator/Pagination/twitter_bootstrap_v4_pagination.html.twig');
        
        // On aligne les sélecteurs au centre de la page
        $derniereDisc->setCustomParameters([
        		"align" => "center"
        ]);

        return $this->render('forum/index.html.twig', [
            'discussions' =>  $derniereDisc,
        ]);
    }

    /**
     * @Route("/forum/creer", name="createDisc")
     */
    public function creation(Request $request, EntityManagerInterface $manager, DiscussionRepository $repo )
    {
        $discussion = new Discussion();
        $formDiscussion = $this->createForm(DiscussionType::class, $discussion);

        $formDiscussion->handleRequest($request);

        if($formDiscussion->isSubmitted() && $formDiscussion->isValid())
        {
            $user = $this->getUser();

            $discussion->setId_utilisateur( $user );
            $discussion->setCreation( new \DateTime() );
            $manager->persist($discussion);
            $manager->flush();
            
            return $this->redirectToRoute('forum');
        }
        
            return $this->render('forum/ajout.html.twig', [
                'fromDisc' =>  $formDiscussion->createview()
        ]);
        
    }

    /**
     * @Route("/forum/{id}", name="readDisc")
     */
    public function afficheDiscussion(Discussion $discussion) {
        return $this->render('forum/discussion_v2.html.twig', [
            'discussion' => $discussion
        ]);
    }
    
    /**
     * @Route("/forum/{id}/reponse", name="repDisc")
     */
    public function reponseDiscussion($id, Request $request, EntityManagerInterface $manager, ReponseRepository $repoR, UtilisateursRepository $repoU) {
    	$reponse = new Reponses();
    	
    	$user = $repoU->findOneBy(["username" => $this->getUser()->getUsername()]);
    	
    	$message = $request->request->get("reponse");
    	if ($user != null && ($message != null && $message != "")) {
    		$reponse->setIdUtilisateur($user->getId());
    		$reponse->setIdDiscussion($id);
    		$reponse->setMessage($message);
    		
    		$manager->persist($reponse);
    		$manager->flush();
    		//dump($message, $user);
    		return $this->redirectToRoute("readDisc", ["id" => $id]);
    	}
    }

    /**
     * @Route("/forum/{id}", name="readDisc", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    /*public function afficher( $id, Request $request, EntityManagerInterface $manager, DiscussionRepository $repo)
    {
        $discussion = $repo->find($id);
        
        
    }*/
}
